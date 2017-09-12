<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;

class CustomerSubscriptionCreatedListener extends CustomerSubscriptionListener
{
    /**
     * @param DispatchableEvent $event
     */
    public function onCustomerSubscriptionCreated(DispatchableEvent $event)
    {
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
                'amount' => $this->getPlanAmount($stripeSubscription),
                'currency' => $stripeSubscription->getPlan()->getCurrency()
            ));
        }

        $this->issueWebClientEvent($webClientData);
        $this->markEntityProcessed();
    }
}
