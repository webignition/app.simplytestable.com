<?php

namespace Tests\AppBundle\Functional\Controller;

use AppBundle\Controller\UserStripeEventController;
use AppBundle\Services\StripeEventService;
use Tests\AppBundle\Factory\UserFactory;

/**
 * @group Controller/UserStripeEventController
 */
class UserStripeEventControllerTest extends AbstractControllerTest
{
    /**
     * @var UserStripeEventController
     */
    private $userStripeEventController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userStripeEventController = self::$container->get(UserStripeEventController::class);
    }

    public function testListActionGetRequest()
    {
        $userFactory = new UserFactory(self::$container);
        $user = $userFactory->createAndActivateUser();

        $router = self::$container->get('router');
        $requestUrl = $router->generate('user_list_stripe_events', [
            'email_canonical' => $user->getEmail(),
            'type' => 'customer.subscription.created',
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $user
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @dataProvider listActionDataProvider
     *
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param string $type
     * @param array $expectedResponseData
     */
    public function testListActionSuccess($stripeEventFixtures, $userName, $type, $expectedResponseData)
    {
        $stripeEventService = self::$container->get(StripeEventService::class);

        $userFactory = new UserFactory(self::$container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();
        $user = $users[$userName];

        $this->setUser($user);

        foreach ($stripeEventFixtures as $stripeEventFixtureData) {
            $eventUser = $users[$stripeEventFixtureData['userName']];

            $stripeEventService->create(
                $stripeEventFixtureData['stripeId'],
                $stripeEventFixtureData['type'],
                false,
                'data',
                $eventUser
            );
        }

        $response = $this->userStripeEventController->listAction($user, $user->getEmailCanonical(), $type);

        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);

        $this->assertCount(count($expectedResponseData), $responseData);

        $this->assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array
     */
    public function listActionDataProvider()
    {
        return [
            'private user, no events' => [
                'stripeEventFixtures' => [],
                'userName' => 'private',
                'type' => null,
                'expectedResponseData' => [],
            ],
            'private user, with type' => [
                'stripeEventFixtures' => [
                    [
                        'stripeId' => 'foo1',
                        'type' => 'customer.subscription.created',
                        'data' => 'data',
                        'userName' => 'private',
                    ],
                    [
                        'stripeId' => 'foo2',
                        'type' => 'customer.subscription.updated',
                        'data' => 'data',
                        'userName' => 'private',
                    ],
                    [
                        'stripeId' => 'foo3',
                        'type' => 'customer.subscription.deleted',
                        'data' => 'data',
                        'userName' => 'private',
                    ],
                ],
                'userName' => 'private',
                'type' => 'customer.subscription.updated',
                'expectedResponseData' => [
                    [
                        'stripe_id' => 'foo2',
                        'type' => 'customer.subscription.updated',
                        'is_live' => false,
                        'stripe_event_data' => 'data',
                        'user' => 'private@example.com',
                        'is_processed' => false,
                    ],
                ],
            ],
        ];
    }
}
