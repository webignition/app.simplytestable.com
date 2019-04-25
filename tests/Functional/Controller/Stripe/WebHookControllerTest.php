<?php

namespace App\Tests\Functional\Controller\Stripe;

use App\Controller\Stripe\WebHookController;
use App\Entity\Stripe\Event as StripeEvent;
use App\Entity\Stripe\Event;
use App\Entity\UserAccountPlan;
use App\Services\HttpClientService;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\StripeEventService;
use App\Services\StripeWebHookMailNotificationSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Tests\Factory\HttpFixtureFactory;
use App\Tests\Factory\StripeEventFixtureFactory;
use App\Tests\Services\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use App\Tests\Functional\Controller\AbstractControllerTest;
use App\Tests\Services\TestHttpClientService;

/**
 * @group Controller/Stripe/WebHookController
 */
class WebHookControllerTest extends AbstractControllerTest
{
    /**
     * @var WebHookController
     */
    private $webHookController;

    /**
     * @var TestHttpClientService
     */
    private $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->webHookController = self::$container->get(WebHookController::class);
        $this->httpClientService = self::$container->get(HttpClientService::class);
    }

    public function testIndexActionPostRequest()
    {
        $this->httpClientService->appendFixtures([
            $this->createPostmarkSuccessResponse(),
        ]);

        $entityManager = self::$container->get(EntityManagerInterface::class);
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

        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $userFactory = self::$container->get(UserFactory::class);
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

        $router = self::$container->get('router');
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
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

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
        $this->httpClientService->appendFixtures([
            $this->createPostmarkSuccessResponse(),
        ]);

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
        $this->httpClientService->appendFixtures([
            $this->createPostmarkSuccessResponse(),
        ]);

        $entityManager = self::$container->get(EntityManagerInterface::class);
        $resqueQueueService = self::$container->get(ResqueQueueService::class);

        $stripeEventRepository = $entityManager->getRepository(Event::class);
        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);

        $resqueQueueService->getResque()->getQueue('stripe-event')->clear();

        $userFactory = self::$container->get(UserFactory::class);
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

        $httpHistory = $this->httpClientService->getHistory();

        $this->assertEquals(1, $httpHistory->count());
        $lastRequest = $httpHistory->getLastRequest();
        $lastRequestUri = $lastRequest->getUri();

        $this->assertStringStartsWith('Postmark-PHP', $lastRequest->getHeaderLine('User-Agent'));
        $this->assertEquals('/email', $lastRequestUri->getPath());

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
            self::$container->get(EntityManagerInterface::class),
            self::$container->get(StripeEventService::class),
            self::$container->get(ResqueQueueService::class),
            self::$container->get(StripeWebHookMailNotificationSender::class),
            $request
        );
    }

    private function createPostmarkSuccessResponse()
    {
        return HttpFixtureFactory::createPostmarkResponse(200, [
            'To' => 'user@example.com',
            'SubmittedAt' => '2014-02-17T07:25:01.4178645-05:00',
            'MessageId' => '0a129aee-e1cd-480d-b08d-4f48548ff48d',
            'ErrorCode' => 0,
            'Message' => 'OK',
        ]);
    }
}
