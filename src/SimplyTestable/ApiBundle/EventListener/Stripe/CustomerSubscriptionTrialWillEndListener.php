<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

class CustomerSubscriptionTrialWillEndListener extends Listener
{
    /**
     * getStripeCustomer
     * getStripeSubscription
     * 
     * getDefaultWebClientData
     * issueWebClientEvent
     * markEntityProcessed
     */
    
    public function onCustomerSubscriptionTrialWillEnd(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {        
        $this->setEvent($event);
        
        $stripeCustomer = $this->getStripeCustomer();
        $stripeSubscription = $this->getStripeSubscription();
        
        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
            'trial_end' => $stripeSubscription->getTrialPeriod()->getEnd(),
            'has_card' => (int)$stripeCustomer->hasCard(),
            'plan_amount' => $stripeSubscription->getPlan()->getAmount(),
            'plan_name' => $stripeSubscription->getPlan()->getName()
        )));     
        
        $this->markEntityProcessed();
    } 
}