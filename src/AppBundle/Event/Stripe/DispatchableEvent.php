<?php

namespace AppBundle\Event\Stripe;

use Symfony\Component\EventDispatcher\Event;
use AppBundle\Entity\Stripe\Event as StripeEvent;
use AppBundle\Services\StripeEventService;

class DispatchableEvent extends Event {
    
    
    /**
     *
     * @var \AppBundle\Entity\Stripe\Event 
     */
    private $entity;
    
    
    /**
     * 
     * @param \AppBundle\Entity\Stripe\Event $stripeEvent
     */
    public function __construct(StripeEvent $stripeEvent) {
        $this->entity = $stripeEvent;
    }
    
    
    /**
     * 
     * @return \AppBundle\Entity\Stripe\Event
     */
    public function getEntity() {
        return $this->entity;
    }    
    
}