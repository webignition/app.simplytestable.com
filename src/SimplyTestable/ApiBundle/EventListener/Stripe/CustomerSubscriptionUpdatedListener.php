<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

class CustomerSubscriptionUpdatedListener extends CustomerSubscriptionListener
{
   
    public function onCustomerSubscriptionUpdated(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {      
        $this->setEvent($event);

        /* @var $stripeEventObject \webignition\Model\Stripe\Event\CustomerSubscriptionUpdated */
        $stripeEventObject = $this->getEventEntity()->getStripeEventObject();
        $webClientEventData = $this->getDefaultWebClientData();        
        $stripeSubscription = $this->getStripeSubscription();

        if ($stripeEventObject->isPlanChange()) {
            $oldPlan = $stripeEventObject->getDataObject()->getPreviousAttributes()->get('plan');
            
            $webClientEventData = array_merge(
                $webClientEventData,
                array(
                    'is_plan_change' => 1,
                    'old_plan' => $oldPlan->getName(),
                    'new_plan' => $stripeSubscription->getPlan()->getName(),
                    'new_amount' => $this->getPlanAmount(),
                    'subscription_status' => $stripeSubscription->getStatus()
                )
            );
            
            if ($stripeSubscription->isTrialing()) {
                $webClientEventData['trial_end'] = $stripeSubscription->getTrialPeriod()->getEnd();
            } 
            
            $this->issueWebClientEvent($webClientEventData);       
            $this->markEntityProcessed();            
        }
        
        if ($stripeEventObject->isStatusChange()) {
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
                'plan_amount' => $this->getPlanAmount(),
                'has_card' => (int)$stripeCustomer->hasCard()
            ));     

            if ($stripeCustomer->hasCard() === false) {
                $this->downgradeToBasicPlan();
            }                
           
            
            $this->issueWebClientEvent($webClientEventData);       
            $this->markEntityProcessed();            
        }
    }


    /**
     * @return int
     */
    private function getPlanAmount() {
        if ($this->hasCustomerDiscount()) {
            return round($this->getStripeSubscription()->getPlan()->getAmount() * ((100 - $this->getCustomerDiscount()->getCoupon()->getPercentOff()) / 100));
        }

        return $this->getStripeSubscription()->getPlan()->getAmount();
    }


    /**
     * @return null|\webignition\Model\Stripe\Discount
     */
    private function getCustomerDiscount() {
        $events = $this->getStripeEventService()->getForUserAndType($this->getEventEntity()->getUser(), ['customer.created', 'customer.updated']);

        foreach ($events as $event) {
            if ($event->getStripeEventObject()->getCustomer()->hasDiscount()) {
                return $event->getStripeEventObject()->getCustomer()->getDiscount();
            }
        }

        return null;
    }


    /**
     * @return bool
     */
    private function hasCustomerDiscount() {
        return !is_null($this->getCustomerDiscount());
    }
}