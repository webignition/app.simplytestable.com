<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;

class CustomerSubscriptionCreatedListener extends AbstractCustomerSubscriptionListener
{
    /**
     * @param DispatchableEvent $event
     */
    public function onCustomerSubscriptionCreated(DispatchableEvent $event)
    {
        $this->setEvent($event);

        $stripeSubscription = $this->getStripeSubscription();

        $webClientData = array_merge($this->getDefaultWebClientData(), [
            'status' => $stripeSubscription->getStatus(),
            'plan_name' => $stripeSubscription->getPlan()->getName()
        ]);

        if ($stripeSubscription->isTrialing()) {
            $user = $this->event->getEntity()->getUser();
            $userAccountPlan = $this->userAccountPlanService->getForUser($user);
            $stripeCustomer = $this->stripeService->getCustomer($userAccountPlan);

            $webClientData = array_merge($webClientData, [
                'has_card' => (int)$stripeCustomer->hasCard(),
                'trial_start' => $stripeSubscription->getTrialPeriod()->getStart(),
                'trial_end' => $stripeSubscription->getTrialPeriod()->getEnd(),
                'trial_period_days' => $stripeSubscription->getPlan()->getTrialPeriodDays()
            ]);
        }

        if ($stripeSubscription->isActive()) {
            $webClientData = array_merge($webClientData, [
                'current_period_start' => $stripeSubscription->getCurrentPeriod()->getStart(),
                'current_period_end' => $stripeSubscription->getCurrentPeriod()->getEnd(),
                'amount' => $this->getPlanAmount($stripeSubscription),
                'currency' => $stripeSubscription->getPlan()->getCurrency()
            ]);
        }

        $this->issueWebClientEvent($webClientData);
        $this->markEntityProcessed();
    }
}
