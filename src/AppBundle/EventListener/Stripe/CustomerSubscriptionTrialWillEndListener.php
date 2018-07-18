<?php

namespace AppBundle\EventListener\Stripe;

use AppBundle\Event\Stripe\DispatchableEvent;

class CustomerSubscriptionTrialWillEndListener extends AbstractCustomerSubscriptionListener
{
    /**
     * @param DispatchableEvent $event
     */
    public function onCustomerSubscriptionTrialWillEnd(DispatchableEvent $event)
    {
        $this->setEvent($event);

        $user = $this->event->getEntity()->getUser();
        $userAccountPlan = $this->userAccountPlanService->getForUser($user);
        $stripeCustomer = $this->stripeService->getCustomer($userAccountPlan);

        $stripeSubscription = $this->getStripeSubscription();

        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), [
            'trial_end' => $stripeSubscription->getTrialPeriod()->getEnd(),
            'has_card' => (int)$stripeCustomer->hasCard(),
            'plan_amount' => $this->getPlanAmount($stripeSubscription),
            'plan_name' => $stripeSubscription->getPlan()->getName(),
            'plan_currency' => $stripeSubscription->getPlan()->getCurrency()
        ]));

        $this->markEntityProcessed();
    }
}
