<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use webignition\Model\Stripe\Discount;
use webignition\Model\Stripe\Event\Customer\Updated as StripeCustomerUpdatedEvent;

class CustomerSubscriptionTrialWillEndListener extends CustomerSubscriptionListener
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
            'plan_amount' => $this->getPlanAmount(),
            'plan_name' => $stripeSubscription->getPlan()->getName(),
            'plan_currency' => $stripeSubscription->getPlan()->getCurrency()
        )));

        $this->markEntityProcessed();
    }

    /**
     * @return int
     */
    private function getPlanAmount()
    {
        $stripeSubscriptionPlanAmount = $this->getStripeSubscription()->getPlan()->getAmount();
        $customerDiscount = $this->getCustomerDiscount();

        if (!empty($customerDiscount)) {
            $percentOff = $customerDiscount->getCoupon()->getPercentOff();

            return (int)round($stripeSubscriptionPlanAmount * ((100 - $percentOff) / 100));
        }

        return $stripeSubscriptionPlanAmount;
    }

    /**
     * @return null|Discount
     */
    private function getCustomerDiscount()
    {
        $events = $this->getStripeEventService()->getForUserAndType(
            $this->getEventEntity()->getUser(),
            [
                'customer.created',
                'customer.updated',
            ]
        );

        foreach ($events as $event) {
            /* @var StripeCustomerUpdatedEvent $stripeCustomerUpdatedEvent */
            $stripeCustomerUpdatedEvent = $event->getStripeEventObject();
            $eventCustomer = $stripeCustomerUpdatedEvent->getCustomer();

            if ($eventCustomer->hasDiscount()) {
                return $eventCustomer->getDiscount();
            }
        }

        return null;
    }
}
