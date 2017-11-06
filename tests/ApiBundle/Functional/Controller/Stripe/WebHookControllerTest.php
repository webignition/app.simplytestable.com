<?php

namespace Tests\ApiBundle\Functional\Controller\Stripe;

use Doctrine\Common\Collections\ArrayCollection;
use SimplyTestable\ApiBundle\Controller\Stripe\WebHookController;
use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use Tests\ApiBundle\Factory\StripeEventFixtureFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\Request;

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

        $this->webHookController = new WebHookController();
        $this->webHookController->setContainer($this->container);
    }

    public function testIndexActionPostRequest()
    {
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

        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);

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

    /**
     * @dataProvider indexActionNoEventContentDataProvider
     *
     * @param array $postData
     * @param string $requestContent
     */
    public function testIndexActionNoEventContent($postData, $requestContent)
    {
        $request = new Request(
            [],
            $postData,
            [],
            [],
            [],
            [],
            $requestContent
        );

        $response = $this->webHookController->indexAction($request);

        $this->assertTrue($response->isClientError());
    }

    /**
     * @return array
     */
    public function indexActionNoEventContentDataProvider()
    {
        return [
            'empty request' => [
                'postData' => [],
                'requestContent' => '',
            ],
            'request content is not json' => [
                'postData' => [],
                'requestContent' => '{id}',
            ],
            'request content lacks object' => [
                'postData' => [],
                'requestContent' => json_encode([
                    'foo' => 'bar',
                ]),
            ],
            'event parameter is not json' => [
                'postData' => [
                    'event' => '{id}',
                ],
                'requestContent' => '',
            ],
            'event parameter lacks object' => [
                'postData' => [
                    'event' => json_encode([
                        'foo' => 'bar',
                    ]),
                ],
                'requestContent' => '',
            ],
        ];
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
        $response = $this->webHookController->indexAction($request);

        $this->assertTrue($response->isSuccessful());
        $decodedResponseData = json_decode($response->getContent(), true);

        $this->assertEquals($stripeId, $decodedResponseData['stripe_id']);
    }

    public function testIndexActionEventAsPostData()
    {
        $request = new Request([], [
            'event' => json_encode(StripeEventFixtureFactory::load('customer.updated')),
        ]);
        $response = $this->webHookController->indexAction($request);

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
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $stripeEventRepository = $this->container->get('simplytestable.repository.stripeevent');
        $userAccountPlanRepository = $this->container->get('simplytestable.repository.useraccountplan');

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
        $response = $this->webHookController->indexAction($request);

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals($stripeId, $responseData['stripe_id']);

        $stripeEvent = $stripeEventRepository->findOneBy([
            'stripeId' => $stripeId,
        ]);

        $this->assertNotNull($stripeEvent);

        $expectedResponseData['stripe_event_data'] = $requestContent;

        $this->assertEquals($expectedResponseData, $responseData);

        $mailSender = $this->container->get('simplytestable.services.postmark.sender');

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
}
