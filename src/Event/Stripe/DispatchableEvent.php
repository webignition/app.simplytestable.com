<?php

namespace App\Event\Stripe;

use Symfony\Component\EventDispatcher\Event;
use App\Entity\Stripe\Event as StripeEvent;
use App\Services\StripeEventService;

class DispatchableEvent extends Event {


    /**
     *
     * @var \App\Entity\Stripe\Event
     */
    private $entity;


    /**
     *
     * @param \App\Entity\Stripe\Event $stripeEvent
     */
    public function __construct(StripeEvent $stripeEvent) {
        $this->entity = $stripeEvent;
    }


    /**
     *
     * @return \App\Entity\Stripe\Event
     */
    public function getEntity() {
        return $this->entity;
    }

}