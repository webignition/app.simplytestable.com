<?php

namespace App\Tests\Services;

use App\Services\StripeEventService;
use App\Entity\Stripe\Event as StripeEvent;
use App\Entity\User;
use App\Tests\Factory\StripeEventFixtureFactory;

class StripeEventFactory
{
    private $stripeEventService;

    public function __construct(StripeEventService $stripeEventService)
    {
        $this->stripeEventService = $stripeEventService;
    }

    /**
     * @param array $stripeEventFixtures
     * @param User $user
     *
     * @return StripeEvent
     */
    public function createEvents($stripeEventFixtures, $user)
    {
        $stripeEvents = [];

        foreach ($stripeEventFixtures as $fixtureName => $modifiers) {
            $stripeEventFixture = StripeEventFixtureFactory::load($fixtureName, $modifiers);

            $stripeEvents[] = $this->stripeEventService->create(
                $stripeEventFixture['id'],
                $stripeEventFixture['type'],
                $stripeEventFixture['livemode'],
                json_encode($stripeEventFixture),
                $user
            );
        }

        return array_pop($stripeEvents);
    }
}
