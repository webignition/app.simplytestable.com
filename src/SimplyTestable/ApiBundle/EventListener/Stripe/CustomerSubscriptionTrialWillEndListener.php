<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;

class CustomerSubscriptionTrialWillEndListener extends AbstractCustomerSubscriptionListener
{
    /**
     * @param DispatchableEvent $event
     */
    public function onCustomerSubscriptionTrialWillEnd(DispatchableEvent $event)
    {
        $this->setEvent($event);

        $stripeCustomer = $this->getStripeCustomer();
        $stripeSubscription = $this->getStripeSubscription();

        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
            'trial_end' => $stripeSubscription->getTrialPeriod()->getEnd(),
            'has_card' => (int)$stripeCustomer->hasCard(),
            'plan_amount' => $this->getPlanAmount($stripeSubscription),
            'plan_name' => $stripeSubscription->getPlan()->getName(),
            'plan_currency' => $stripeSubscription->getPlan()->getCurrency()
        )));

        $this->markEntityProcessed();
    }
}
