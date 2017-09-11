<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\EventListener\Stripe;

use SimplyTestable\ApiBundle\Tests\Factory\StripeEventFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

abstract class AbstractStripeEventListenerTest extends BaseSimplyTestableTestCase
{
    public function createStripeEvents($stripeEventFixtures, $user)
    {
        $stripeEventService = $this->container->get('simplytestable.services.stripeeventservice');

        $stripeEvents = [];

        foreach ($stripeEventFixtures as $fixtureName => $modifiers) {
            $stripeEventFixture = StripeEventFixtureFactory::load($fixtureName, $modifiers);

            $stripeEvents[] = $stripeEventService->create(
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
