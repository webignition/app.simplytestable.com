<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

class CustomerSubscriptionDeletedListener extends CustomerSubscriptionListener
{

    public function onCustomerSubscriptionDeleted(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $this->setEvent($event);       
        
        $stripeSubscription = $this->getStripeSubscription();
        
        if ($stripeSubscription->wasCancelledDuringTrial()) {
            $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(           
                'plan_name' => $stripeSubscription->getPlan()->getName(),
                'actioned_by' => 'user',
                'is_during_trial' => 1,
                'trial_days_remaining' => $this->getUserAccountPlanFromEvent()->getStartTrialPeriod()
            )));
            
            $this->markEntityProcessed();
            return;
        }
        
        if ($this->hasInvoicePaymentFailedEventForSubscription($stripeSubscription)) {
            // System has cancelled following payment failure
            $this->downgradeToBasicPlan();            
            
            $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(           
                'plan_name' => $stripeSubscription->getPlan()->getName(),
                'actioned_by' => 'system'
            )));        
            $this->markEntityProcessed();             
        }
        
        if (!$this->hasTrialToActiveStatusChangeEvent()) {
            // User has canceled after trial            
            $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(           
                'plan_name' => $stripeSubscription->getPlan()->getName(),
                'actioned_by' => 'user',
                'is_during_trial' => 0
            )));        
            $this->markEntityProcessed();              
        }
      
        $this->markEntityProcessed();
    }
    
    
    private function hasInvoicePaymentFailedEventForSubscription(\webignition\Model\Stripe\Subscription $subscription) {
        $paymentFailedEvents = $this->getStripeEventService()->getForUserAndType(
            $this->getEventEntity()->getUser(),
            'invoice.payment_failed'
        );

        foreach ($paymentFailedEvents as $paymentFailedEvent) {
            if ($paymentFailedEvent->getStripeEventObject()->getDataObject()->getObject()->isForSubscription($subscription)) {
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasTrialToActiveStatusChangeEvent() {
        return !is_null($this->getMostRecentTrialToActiveStatusChangeEvent());
    }
    
    
    
    /**
     * 
     * @return \webignition\Model\Stripe\Event\Event|
     */
    private function getMostRecentTrialToActiveStatusChangeEvent() {
        $customerSubscriptionUpdatedEvents = $this->getStripeEventService()->getForUserAndType(
            $this->getEventEntity()->getUser(),
            'customer.subscription.updated'
        );
        
        foreach ($customerSubscriptionUpdatedEvents as $customerSubscriptionUpdatedEvent) {            
            if ($customerSubscriptionUpdatedEvent->getStripeEventObject()->hasStatusChange('trialing:active')) {
                return $customerSubscriptionUpdatedEvent->getStripeEventObject();
            }
        }        
        
        return null;
    }
   
}