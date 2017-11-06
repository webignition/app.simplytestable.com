<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use webignition\Model\Stripe\Subscription as StripeSubscriptionModel;
use webignition\Model\Stripe\Discount as StripeDiscountModel;
use webignition\Model\Stripe\Event\Customer\Updated as StripeCustomerUpdatedEvent;

abstract class AbstractCustomerSubscriptionListener extends AbstractListener
{
    /**
     * @return StripeSubscriptionModel
     */
    protected function getStripeSubscription()
    {
        /* @var StripeSubscriptionModel $stripeSubscriptionModel */
        $stripeSubscriptionModel = $this->event->getEntity()->getStripeEventObject()->getDataObject()->getObject();

        return $stripeSubscriptionModel;
    }

    /**
     * @param StripeSubscriptionModel $subscription
     *
     * @return int
     */
    protected function getPlanAmount(StripeSubscriptionModel $subscription)
    {
        $stripeSubscriptionPlanAmount = $subscription->getPlan()->getAmount();
        $customerDiscount = $this->getCustomerDiscount();

        if (!empty($customerDiscount)) {
            $percentOff = $customerDiscount->getCoupon()->getPercentOff();

            return (int)round($stripeSubscriptionPlanAmount * ((100 - $percentOff) / 100));
        }

        return $stripeSubscriptionPlanAmount;
    }

    /**
     * @return StripeDiscountModel|null
     */
    private function getCustomerDiscount()
    {
        $events = $this->stripeEventService->getForUserAndType(
            $this->event->getEntity()->getUser(),
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
