<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

class CustomerSubscriptionCreatedListener extends CustomerSubscriptionListener
{
   
    public function onCustomerSubscriptionCreated(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $this->setEvent($event);
        
        $stripeSubscription = $this->getStripeSubscription();
        
        $webClientData = array_merge($this->getDefaultWebClientData(), array(
            'status' => $stripeSubscription->getStatus(),            
            'plan_name' => $stripeSubscription->getPlan()->getName()
        ));
        
        if ($stripeSubscription->isTrialing()) {
            $webClientData = array_merge($webClientData, array(
                'has_card' => (int)$this->getStripeCustomer()->hasCard(),
                'trial_start' => $stripeSubscription->getTrialPeriod()->getStart(),
                'trial_end' => $stripeSubscription->getTrialPeriod()->getEnd(),
                'trial_period_days' => $stripeSubscription->getPlan()->getTrialPeriodDays()
            ));        
        }
        
        if ($stripeSubscription->isActive()) {            
            $webClientData = array_merge($webClientData, array(
                'current_period_start' => $stripeSubscription->getCurrentPeriod()->getStart(),
                'current_period_end' => $stripeSubscription->getCurrentPeriod()->getEnd(),
                'amount' => $stripeSubscription->getPlan()->getAmount(),
                'currency' => $stripeSubscription->getPlan()->getCurrency()
            ));             
        }
        
        $this->issueWebClientEvent($webClientData);        
        $this->markEntityProcessed();
    }
}