<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use webignition\Model\Stripe\Event\CustomerSubscriptionUpdated;
use webignition\Model\Stripe\Discount;
use webignition\Model\Stripe\Event\Customer\Updated as StripeCustomerUpdatedEvent;
use webignition\Model\Stripe\Event\Data as StripeEventData;
use webignition\Model\Stripe\Subscription as StripeSubscriptionModel;

class CustomerSubscriptionUpdatedListener extends CustomerSubscriptionListener
{
    /**
     * @param DispatchableEvent $event
     */
    public function onCustomerSubscriptionUpdated(DispatchableEvent $event)
    {
        $this->setEvent($event);

        /* @var $stripeEventObject CustomerSubscriptionUpdated */
        $stripeEventObject = $this->getEventEntity()->getStripeEventObject();
        $stripeSubscription = $this->getStripeSubscription();
        $webClientEventData = array_merge($this->getDefaultWebClientData(), [
            'currency' => $stripeSubscription->getPlan()->getCurrency()
        ]);

        if ($stripeEventObject->isPlanChange()) {
            $oldPlan = $stripeEventObject->getDataObject()->getPreviousAttributes()->get('plan');

            $webClientEventData = array_merge(
                $webClientEventData,
                [
                    'is_plan_change' => 1,
                    'old_plan' => $oldPlan->getName(),
                    'new_plan' => $stripeSubscription->getPlan()->getName(),
                    'new_amount' => $this->getPlanAmount($stripeSubscription),
                    'subscription_status' => $stripeSubscription->getStatus()
                ]
            );

            if ($stripeSubscription->isTrialing()) {
                $webClientEventData['trial_end'] = $stripeSubscription->getTrialPeriod()->getEnd();
            }

            $this->issueWebClientEvent($webClientEventData);
            $this->markEntityProcessed();
        }

        if ($stripeEventObject->isStatusChange()) {
            /* @var StripeEventData $stripeEventData */
            $stripeEventData = $stripeEventObject->getDataObject();
            $stripeEventDataPreviousAttributes = $stripeEventData->getPreviousAttributes();

            $previousSubscriptionStatus = $stripeEventDataPreviousAttributes->get('status');
            $subscriptionStatus = $stripeSubscription->getStatus();

            if (!($previousSubscriptionStatus == 'trialing' && $subscriptionStatus == 'active')) {
                $this->markEntityProcessed();

                return;
            };

            $previousSubscription = new StripeSubscriptionModel(json_encode(
                $stripeEventDataPreviousAttributes->toArray()
            ));
            $stripeCustomer = $this->getStripeCustomer();

            $webClientEventData = array_merge(
                $webClientEventData,
                [
                    'is_status_change' => 1,
                    'previous_subscription_status' => $previousSubscription->getStatus(),
                    'subscription_status' => $stripeSubscription->getStatus(),
                    'plan_name' => $stripeSubscription->getPlan()->getName(),
                    'plan_amount' => $this->getPlanAmount($stripeSubscription),
                    'has_card' => (int)$stripeCustomer->hasCard()
                ]
            );

            if ($stripeCustomer->hasCard() === false) {
                $this->downgradeToBasicPlan();
            }

            $this->issueWebClientEvent($webClientEventData);
            $this->markEntityProcessed();
        }
    }
}
