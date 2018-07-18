<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class StripeEventServiceTest extends AbstractBaseTestCase
{
    /**
     * @var StripeEventService
     */
    private $stripeEventService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->stripeEventService = self::$container->get(StripeEventService::class);
    }

    public function testCreateWithExistingEvent()
    {
        $stripeId = 'foo';

        $event = $this->stripeEventService->create(
            $stripeId,
            'customer.subscription.updated',
            false,
            'data',
            null
        );

        $this->assertEquals(
            $event,
            $this->stripeEventService->create(
                $stripeId,
                'customer.subscription.updated',
                false,
                'data',
                null
            )
        );
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $stripeId
     * @param string $type
     * @param bool $isLiveMode
     * @param string $data
     * @param string $userName
     */
    public function testCreate(
        $stripeId,
        $type,
        $isLiveMode,
        $data,
        $userName
    ) {
        if (is_null($userName)) {
            $user = null;
        } else {
            $userFactory = new UserFactory(self::$container);
            $users = $userFactory->createPublicAndPrivateUserSet();
            $user = $users[$userName];
        }

        $stripeEvent = $this->stripeEventService->create(
            $stripeId,
            $type,
            $isLiveMode,
            $data,
            $user
        );

        $this->assertInstanceOf(StripeEvent::class, $stripeEvent);

        $this->assertEquals($stripeId, $stripeEvent->getStripeId());
        $this->assertEquals($type, $stripeEvent->getType());
        $this->assertEquals($isLiveMode, $stripeEvent->getIsLive());
        $this->assertEquals($data, $stripeEvent->getStripeEventData());
        $this->assertEquals($user, $stripeEvent->getUser());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'no user' => [
                'stripeId' => 'foo1',
                'type' => 'customer.subscription.created',
                'isLiveMode' => false,
                'data' => 'data1',
                'userName' => null,
            ],
            'has user' => [
                'stripeId' => 'foo2',
                'type' => 'customer.subscription.updated',
                'isLiveMode' => true,
                'data' => 'data2',
                'userName' => 'private',
            ],
        ];
    }

    /**
     * @dataProvider getForUserAndTypeDataProvider
     *
     * @param array $stripeEventFixtures
     * @param string $userName
     * @param string|string[] $type
     * @param string[] $expectedEventStripeIds
     */
    public function testGetForUserAndType($stripeEventFixtures, $userName, $type, $expectedEventStripeIds)
    {
        $userFactory = new UserFactory(self::$container);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $user = $users[$userName];

        foreach ($stripeEventFixtures as $stripeEventFixtureData) {
            $eventUser = $users[$stripeEventFixtureData['userName']];

            $this->stripeEventService->create(
                $stripeEventFixtureData['stripeId'],
                $stripeEventFixtureData['type'],
                false,
                'data',
                $eventUser
            );
        }

        $events = $this->stripeEventService->getForUserAndType($user, $type);

        $this->assertCount(count($expectedEventStripeIds), $events);

        $stripeEventIds = [];

        foreach ($events as $event) {
            $stripeEventIds[] = $event->getStripeId();
        }

        $this->assertEquals($expectedEventStripeIds, $stripeEventIds);
    }

    /**
     * @return array
     */
    public function getForUserAndTypeDataProvider()
    {
        return [
            'public user, no events' => [
                'stripeEventFixtures' => [],
                'userName' => 'public',
                'type' => 'customer.subscription.updated',
                'expectedEventData' => [],
            ],
            'public user, has events' => [
                'stripeEventFixtures' => [
                    [
                        'stripeId' => 'foo1',
                        'type' => 'customer.subscription.created',
                        'data' => 'data',
                        'userName' => 'public',
                    ],
                    [
                        'stripeId' => 'foo2',
                        'type' => 'customer.subscription.updated',
                        'data' => 'data',
                        'userName' => 'public',
                    ],
                    [
                        'stripeId' => 'foo3',
                        'type' => 'customer.subscription.updated',
                        'data' => 'data',
                        'userName' => 'public',
                    ],
                    [
                        'stripeId' => 'foo4',
                        'type' => 'customer.subscription.updated',
                        'data' => 'data',
                        'userName' => 'public',
                    ],
                    [
                        'stripeId' => 'foo5',
                        'type' => 'customer.subscription.deleted',
                        'data' => 'data',
                        'userName' => 'public',
                    ],
                ],
                'userName' => 'public',
                'type' => 'customer.subscription.updated',
                'expectedEventStripeIds' => [
                    'foo4',
                    'foo3',
                    'foo2',
                ],
            ],
            'private user, has events' => [
                'stripeEventFixtures' => [
                    [
                        'stripeId' => 'foo1',
                        'type' => 'customer.subscription.created',
                        'data' => 'data',
                        'userName' => 'public',
                    ],
                    [
                        'stripeId' => 'foo2',
                        'type' => 'customer.subscription.updated',
                        'data' => 'data',
                        'userName' => 'public',
                    ],
                    [
                        'stripeId' => 'foo3',
                        'type' => 'customer.subscription.deleted',
                        'data' => 'data',
                        'userName' => 'public',
                    ],
                    [
                        'stripeId' => 'foo4',
                        'type' => 'customer.subscription.created',
                        'data' => 'data',
                        'userName' => 'private',
                    ],
                    [
                        'stripeId' => 'foo5',
                        'type' => 'customer.subscription.updated',
                        'data' => 'data',
                        'userName' => 'private',
                    ],
                    [
                        'stripeId' => 'foo6',
                        'type' => 'customer.subscription.deleted',
                        'data' => 'data',
                        'userName' => 'private',
                    ],
                ],
                'userName' => 'private',
                'type' => [
                    'customer.subscription.created',
                    'customer.subscription.deleted',
                ],
                'expectedEventStripeIds' => [
                    'foo6',
                    'foo4',
                ],
            ],
        ];
    }
}
