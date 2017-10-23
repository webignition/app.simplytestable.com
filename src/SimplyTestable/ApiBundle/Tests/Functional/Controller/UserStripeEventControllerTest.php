<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\UserStripeEventController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class UserStripeEventControllerTest extends BaseSimplyTestableTestCase
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

        $this->userStripeEventController = new UserStripeEventController();
        $this->userStripeEventController->setContainer($this->container);
    }

    public function testListActionGetRequest()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();

        $router = $this->container->get('router');
        $requestUrl = $router->generate('user_list_stripe_events', [
            'email_canonical' => $user->getEmail(),
            'type' => 'customer.subscription.created',
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $user
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @dataProvider listActionClientFailureDataProvider
     *
     * @param string $userName
     * @param string $emailCanonical
     */
    public function testListActionClientFailure($userName, $emailCanonical)
    {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();

        $user = $users[$userName];
        $this->setUser($user);

        $response = $this->userStripeEventController->listAction($emailCanonical, 'foo');

        $this->assertTrue($response->isClientError());
    }

    /**
     * @return array
     */
    public function listActionClientFailureDataProvider()
    {
        return [
            'public user' => [
                'userName' => 'public',
                'emailCanonical' => 'foo@example.com',
            ],
            'incorrect user' => [
                'userName' => 'private',
                'emailCanonical' => 'foo@example.com',
            ],
        ];
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
        $stripeEventService = $this->container->get('simplytestable.services.stripeeventservice');

        $userFactory = new UserFactory($this->container);
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

        $response = $this->userStripeEventController->listAction($user->getEmailCanonical(), $type);

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
