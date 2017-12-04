<?php

namespace Tests\ApiBundle\Functional\Controller\Stripe;

use Doctrine\Common\Collections\ArrayCollection;
use SimplyTestable\ApiBundle\Controller\Stripe\WebHookController;
use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Entity\Stripe\Event;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Services\Postmark\Sender;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\StripeWebHookMailNotificationSender;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tests\ApiBundle\Factory\StripeEventFixtureFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Controller/Stripe/WebHookController
 */
class WebHookControllerTest extends AbstractBaseTestCase
{
    /**
     * @var WebHookController
     */
    private $webHookController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->webHookController = $this->container->get(WebHookController::class);
    }

    public function testIndexActionPostRequest()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);

        $fixtureName = 'customer.subscription.created.active';
        $fixtureModifications = [
            'data' => [
                'object' => [
                    'customer' => 'stripe-customer',
                ],
            ],
        ];

        $stripeId = 'foo';
        $stripeCustomer = 'stripe-customer';

        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        /* @var UserAccountPlan $userAccountPlan */
        $userAccountPlan = $userAccountPlanRepository->findOneBy([
            'user' => $user,
        ]);

        $userAccountPlan->setStripeCustomer($stripeCustomer);
        $entityManager->persist($userAccountPlan);
        $entityManager->flush($userAccountPlan);

        $fixtureModifications = array_merge($fixtureModifications, [
            'id' => $stripeId,
        ]);

        $requestContent = json_encode(StripeEventFixtureFactory::load($fixtureName, $fixtureModifications));

        $router = $this->container->get('router');
        $requestUrl = $router->generate('stripe_webhook_receiver');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => null,
            'parameters' => [
                'event' => $requestContent,
            ],
        ]);

        $response = $this->getClientResponse();
        $this->assertTrue($response->isSuccessful());
    }

    public function testIndexActionStripeEventAlreadyExists()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $stripeId = 'foo';
        $stripeEvent = new StripeEvent();
        $stripeEvent->setStripeId($stripeId);
        $stripeEvent->setType('not relevant');
        $stripeEvent->setIsLive(false);

        $entityManager->persist($stripeEvent);
        $entityManager->flush($stripeEvent);

        $requestContent = json_encode(StripeEventFixtureFactory::load(
            'customer.subscription.created.active',
            [
                'id' => $stripeId,
            ]
        ));

        $request = new Request([], [], [], [], [], [], $requestContent);
        $response = $this->callIndexAction($request);

        $this->assertTrue($response->isSuccessful());
        $decodedResponseData = json_decode($response->getContent(), true);

        $this->assertEquals($stripeId, $decodedResponseData['stripe_id']);
    }

    public function testIndexActionEventAsPostData()
    {
        $request = new Request([], [
            'event' => json_encode(StripeEventFixtureFactory::load('customer.updated')),
        ]);
        $response = $this->callIndexAction($request);

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @dataProvider indexActionSuccessDataProvider
     *
     * @param string $fixtureName
     * @param array $fixtureModifications
     * @param string $stripeId
     * @param string $stripeCustomer
     * @param array $expectedResponseData
     */
    public function testIndexActionSuccess(
        $fixtureName,
        $fixtureModifications,
        $stripeId,
        $stripeCustomer,
        $expectedResponseData
    ) {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $resqueQueueService = $this->container->get(ResqueQueueService::class);

        $stripeEventRepository = $entityManager->getRepository(Event::class);
        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        /* @var UserAccountPlan $userAccountPlan */
        $userAccountPlan = $userAccountPlanRepository->findOneBy([
            'user' => $user,
        ]);

        $userAccountPlan->setStripeCustomer($stripeCustomer);
        $entityManager->persist($userAccountPlan);
        $entityManager->flush($userAccountPlan);

        $fixtureModifications = array_merge($fixtureModifications, [
            'id' => $stripeId,
        ]);

        $requestContent = json_encode(StripeEventFixtureFactory::load($fixtureName, $fixtureModifications));

        $request = new Request([], [], [], [], [], [], $requestContent);
        $response = $this->callIndexAction($request);

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals($stripeId, $responseData['stripe_id']);

        $stripeEvent = $stripeEventRepository->findOneBy([
            'stripeId' => $stripeId,
        ]);

        $this->assertNotNull($stripeEvent);

        $expectedResponseData['stripe_event_data'] = $requestContent;

        $this->assertEquals($expectedResponseData, $responseData);

        $mailSender = $this->container->get(Sender::class);

        /* @var ArrayCollection $mailHistory */
        $mailHistory = $mailSender->getHistory();

        $this->assertCount(1, $mailHistory);

        $this->assertTrue($resqueQueueService->contains(
            'stripe-event',
            ['stripeId' => $stripeId]
        ));
    }

    /**
     * @return array
     */
    public function indexActionSuccessDataProvider()
    {
        return [
            'customer.subscription.created' => [
                'fixtureName' => 'customer.subscription.created.active',
                'fixtureModifications' => [
                    'data' => [
                        'object' => [
                            'customer' => 'stripe-customer',
                        ],
                    ],
                ],
                'stripeId' => 'foo',
                'stripeCustomer' => 'stripe-customer',
                'expectedResponseData' => [
                    'stripe_id' =>  'foo',
                    'user' => 'user@example.com',
                    'type' => 'customer.subscription.created',
                    'is_live' => false,
                    'is_processed' => false,
                ],
            ],
            'customer.subscription.deleted' => [
                'fixtureName' => 'customer.subscription.deleted',
                'fixtureModifications' => [
                    'data' => [
                        'object' => [
                            'customer' => 'stripe-customer',
                        ],
                    ],
                ],
                'stripeId' => 'foo',
                'stripeCustomer' => 'stripe-customer',
                'expectedResponseData' => [
                    'stripe_id' =>  'foo',
                    'user' => 'user@example.com',
                    'type' => 'customer.subscription.deleted',
                    'is_live' => false,
                    'is_processed' => false,
                ],
            ],
            'customer.subscription.trial_will_end' => [
                'fixtureName' => 'customer.subscription.trial_will_end',
                'fixtureModifications' => [
                    'data' => [
                        'object' => [
                            'customer' => 'stripe-customer',
                        ],
                    ],
                ],
                'stripeId' => 'foo',
                'stripeCustomer' => 'stripe-customer',
                'expectedResponseData' => [
                    'stripe_id' =>  'foo',
                    'user' => 'user@example.com',
                    'type' => 'customer.subscription.trial_will_end',
                    'is_live' => true,
                    'is_processed' => false,
                ],
            ],
            'customer.subscription.updated' => [
                'fixtureName' => 'customer.subscription.updated.planchange',
                'fixtureModifications' => [
                    'data' => [
                        'object' => [
                            'customer' => 'stripe-customer',
                        ],
                    ],
                ],
                'stripeId' => 'foo',
                'stripeCustomer' => 'stripe-customer',
                'expectedResponseData' => [
                    'stripe_id' =>  'foo',
                    'user' => 'user@example.com',
                    'type' => 'customer.subscription.updated',
                    'is_live' => true,
                    'is_processed' => false,
                ],
            ],
            'customer.updated' => [
                'fixtureName' => 'customer.updated',
                'fixtureModifications' => [
                    'data' => [
                        'object' => [
                            'id' => 'stripe-customer',
                        ],
                    ],
                ],
                'stripeId' => 'foo',
                'stripeCustomer' => 'stripe-customer',
                'expectedResponseData' => [
                    'stripe_id' =>  'foo',
                    'user' => 'user@example.com',
                    'type' => 'customer.updated',
                    'is_live' => false,
                    'is_processed' => false,
                ],
            ],
            'invoice.payment_failed; unknown user' => [
                'fixtureName' => 'invoice.payment_failed',
                'fixtureModifications' => [
                    'data' => [
                        'object' => [
                            'customer' => 'stripe-customer',
                        ],
                    ],
                ],
                'stripeId' => 'foo',
                'stripeCustomer' => 'stripe-customer-foo',
                'expectedResponseData' => [
                    'stripe_id' =>  'foo',
                    'type' => 'invoice.payment_failed',
                    'is_live' => true,
                    'is_processed' => false,
                ],
            ],
            'invoice.payment_failed; known user' => [
                'fixtureName' => 'invoice.payment_failed',
                'fixtureModifications' => [
                    'data' => [
                        'object' => [
                            'customer' => 'stripe-customer',
                        ],
                    ],
                ],
                'stripeId' => 'foo',
                'stripeCustomer' => 'stripe-customer',
                'expectedResponseData' => [
                    'stripe_id' =>  'foo',
                    'user' => 'user@example.com',
                    'type' => 'invoice.payment_failed',
                    'is_live' => true,
                    'is_processed' => false,
                ],
            ],
            'invoice.payment_succeeded' => [
                'fixtureName' => 'invoice.payment_succeeded',
                'fixtureModifications' => [
                    'data' => [
                        'object' => [
                            'customer' => 'stripe-customer',
                        ],
                    ],
                ],
                'stripeId' => 'foo',
                'stripeCustomer' => 'stripe-customer',
                'expectedResponseData' => [
                    'stripe_id' =>  'foo',
                    'user' => 'user@example.com',
                    'type' => 'invoice.payment_succeeded',
                    'is_live' => true,
                    'is_processed' => false,
                ],
            ],
        ];
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    private function callIndexAction(Request $request)
    {
        return $this->webHookController->indexAction(
            $this->container->get('doctrine.orm.entity_manager'),
            $this->container->get(StripeEventService::class),
            $this->container->get(ResqueQueueService::class),
            $this->container->get(ResqueJobFactory::class),
            $this->container->get(StripeWebHookMailNotificationSender::class),
            $request
        );
    }
}
