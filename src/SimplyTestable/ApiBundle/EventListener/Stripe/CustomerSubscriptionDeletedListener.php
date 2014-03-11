<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

class CustomerSubscriptionDeletedListener extends Listener
{
    /**
     * 
     * getStripeSubscription
     * downgradeToBasicPlan
     * getUserAccountPlanFromEvent
     * 
     * getDefaultWebClientData
     * issueWebClientEvent
     * markEntityProcessed
     */

    public function onCustomerSubscriptionDeleted(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $this->setEvent($event);       
        
        $stripeSubscription = $this->getStripeSubscription();
        
        if ($stripeSubscription->wasCancelledDuringTrial()) {                       
            $webClientData = array_merge($this->getDefaultWebClientData(), array(           
                'plan_name' => $stripeSubscription->getPlan()->getName(),
                'actioned_by' => 'user',
                'is_during_trial' => 1,
                'trial_days_remaining' => $this->getUserAccountPlanFromEvent()->getStartTrialPeriod()
            ));            
        } else {
            $paymentFailedEvents = $this->getStripeEventService()->getForUserAndType(
                $this->getEventEntity()->getUser(),
                'invoice.payment_failed'
            );
            
            $hasInvoicePaymentFailedEventForSubscription = false;
            
            foreach ($paymentFailedEvents as $paymentFailedEvent) {
                if ($paymentFailedEvent->getStripeEventObject()->getDataObject()->getObject()->isForSubscription($stripeSubscription->getId())) {
                    $hasInvoicePaymentFailedEventForSubscription = true;
                }
            }
            
            if ($hasInvoicePaymentFailedEventForSubscription === false) {
                // User has canceled after trial
                $webClientData = array_merge($this->getDefaultWebClientData(), array(           
                    'plan_name' => $stripeSubscription->getPlan()->getName(),
                    'actioned_by' => 'user',
                    'is_during_trial' => 0
                ));                  
            } else {
                // System has cancelled following payment failure
                $webClientData = array_merge($this->getDefaultWebClientData(), array(           
                    'plan_name' => $stripeSubscription->getPlan()->getName(),
                    'actioned_by' => 'system'
                )); 
                
                $this->downgradeToBasicPlan();
            }          
        }
        


        
        $this->issueWebClientEvent($webClientData);        
        $this->markEntityProcessed();
    }    
}