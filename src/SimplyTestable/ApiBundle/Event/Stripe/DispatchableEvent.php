<?php

namespace SimplyTestable\ApiBundle\Event\Stripe;

use Symfony\Component\EventDispatcher\Event;
use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Services\StripeEventService;

class DispatchableEvent extends Event {
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Stripe\Event 
     */
    private $entity;
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Stripe\Event $stripeEvent
     */
    public function __construct(StripeEvent $stripeEvent) {
        $this->entity = $stripeEvent;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\Stripe\Event
     */
    public function getEntity() {
        return $this->entity;
    }    
    
}