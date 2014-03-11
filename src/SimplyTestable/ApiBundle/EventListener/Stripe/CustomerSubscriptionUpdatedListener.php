<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

class CustomerSubscriptionUpdatedListener extends CustomerSubscriptionListener
{
   
    public function onCustomerSubscriptionUpdated(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {      
        $this->setEvent($event);
        
        $stripeEventObject = $this->getEventEntity()->getStripeEventObject();
        $webClientEventData = $this->getDefaultWebClientData();        
        $stripeSubscription = $this->getStripeSubscription();
        
        $isPlanChange = $stripeEventObject->getDataObject()->hasPreviousAttributes() && $stripeEventObject->getDataObject()->getPreviousAttributes()->containsKey('plan');
        
        if ($isPlanChange) {
            $oldPlan = $stripeEventObject->getDataObject()->getPreviousAttributes()->get('plan');
            
            $webClientEventData = array_merge(
                $webClientEventData,
                array(
                    'is_plan_change' => 1,
                    'old_plan' => $oldPlan->getName(),
                    'new_plan' => $stripeSubscription->getPlan()->getName(),
                    'new_amount' => $stripeSubscription->getPlan()->getAmount(),
                    'subscription_status' => $stripeSubscription->getStatus()
                )
            );
            
            if ($stripeSubscription->isTrialing()) {
                $webClientEventData['trial_end'] = $stripeSubscription->getTrialPeriod()->getEnd();
            } 
            
            $this->issueWebClientEvent($webClientEventData);       
            $this->markEntityProcessed();            
        }
        
        $isStatusChange = $stripeEventObject->getDataObject()->hasPreviousAttributes() && $stripeEventObject->getDataObject()->getPreviousAttributes()->containsKey('status');
        
        if ($isStatusChange) {
            $statusTransition = $stripeEventObject->getDataObject()->getPreviousAttributes()->get('status') . '-to-' . $stripeSubscription->getStatus();
            
            if ($statusTransition != 'trialing-to-active') {
                $this->markEntityProcessed();
                return;
            };
            
            $previousSubscription = new \webignition\Model\Stripe\Subscription(json_encode($stripeEventObject->getDataObject()->getPreviousAttributes()->toArray()));
            $stripeCustomer = $this->getStripeCustomer();
            
            $webClientEventData = array_merge($webClientEventData, array(
                'is_status_change' => 1,
                'previous_subscription_status' => $previousSubscription->getStatus(),
                'subscription_status' => $stripeSubscription->getStatus(),
                'plan_name' => $stripeSubscription->getPlan()->getName(),
                'plan_amount' => $stripeSubscription->getPlan()->getAmount(),
                'has_card' => (int)$stripeCustomer->hasCard()
            ));     

            if ($stripeCustomer->hasCard() === false) {
                $this->downgradeToBasicPlan();
            }                
           
            
            $this->issueWebClientEvent($webClientEventData);       
            $this->markEntityProcessed();            
        }
    }
}