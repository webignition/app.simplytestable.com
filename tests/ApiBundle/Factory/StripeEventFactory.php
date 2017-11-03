<?php

namespace Tests\ApiBundle\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;
use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Entity\User;

class StripeEventFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $stripeEventFixtures
     * @param User $user
     *
     * @return StripeEvent
     */
    public function createEvents($stripeEventFixtures, $user)
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
