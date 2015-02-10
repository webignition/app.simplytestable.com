<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

class CustomerSubscriptionTrialWillEndListener extends CustomerSubscriptionListener
{
    
    public function onCustomerSubscriptionTrialWillEnd(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {        
        $this->setEvent($event);
        
        $stripeCustomer = $this->getStripeCustomer();
        $stripeSubscription = $this->getStripeSubscription();
        
        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
            'trial_end' => $stripeSubscription->getTrialPeriod()->getEnd(),
            'has_card' => (int)$stripeCustomer->hasCard(),
            'plan_amount' => $this->getPlanAmount(),
            'plan_name' => $stripeSubscription->getPlan()->getName(),
            'plan_currency' => $stripeSubscription->getPlan()->getCurrency()
        )));     
        
        $this->markEntityProcessed();
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