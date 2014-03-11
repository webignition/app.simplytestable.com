<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

abstract class CustomerSubscriptionListener extends Listener
{
    /**
     * 
     * @return \webignition\Model\Stripe\Subscription
     */
    protected function getStripeSubscription() {
        return $this->getEventEntity()->getStripeEventObject()->getDataObject()->getObject();
    }
}