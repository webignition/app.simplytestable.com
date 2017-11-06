<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use webignition\Model\Stripe\Event\CustomerSubscriptionUpdated;
use webignition\Model\Stripe\Event\Event;
use webignition\Model\Stripe\Invoice\Invoice;
use webignition\Model\Stripe\Subscription;

class CustomerSubscriptionDeletedListener extends AbstractCustomerSubscriptionListener
{
    /**
     * @var AccountPlanService
     */
    private $accountPlanService;

    /**
     * @param StripeService $stripeService
     * @param StripeEventService $stripeEventService
     * @param UserAccountPlanService $userAccountPlanService
     * @param HttpClientService $httpClientService
     * @param AccountPlanService $accountPlanService
     * @param $webClientProperties
     */
    public function __construct(
        StripeEventService $stripeEventService,
        HttpClientService $httpClientService,
        $webClientProperties,
        StripeService $stripeService,
        UserAccountPlanService $userAccountPlanService,
        AccountPlanService $accountPlanService
    ) {
        parent::__construct(
            $stripeEventService,
            $httpClientService,
            $webClientProperties,
            $stripeService,
            $userAccountPlanService
        );

        $this->accountPlanService = $accountPlanService;
    }

    /**
     * @param DispatchableEvent $event
     */
    public function onCustomerSubscriptionDeleted(DispatchableEvent $event)
    {
        $this->setEvent($event);

        $stripeSubscription = $this->getStripeSubscription();

        if ($stripeSubscription->wasCancelledDuringTrial()) {
            $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
                'plan_name' => $stripeSubscription->getPlan()->getName(),
                'actioned_by' => 'user',
                'is_during_trial' => 1,
                'trial_days_remaining' => $this->getUserAccountPlanFromEvent()->getStartTrialPeriod()
            )));

            $this->markEntityProcessed();

            return;
        }

        if ($this->hasInvoicePaymentFailedEventForSubscription($stripeSubscription)) {
            // System has cancelled following payment failure
            $this->downgradeToBasicPlan();

            $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
                'plan_name' => $stripeSubscription->getPlan()->getName(),
                'actioned_by' => 'system'
            )));
            $this->markEntityProcessed();

            return;
        }

        if (!$this->hasTrialToActiveStatusChangeEvent()) {
            // User has canceled after trial
            $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
                'plan_name' => $stripeSubscription->getPlan()->getName(),
                'actioned_by' => 'user',
                'is_during_trial' => 0
            )));
            $this->markEntityProcessed();

            return;
        }

        $this->markEntityProcessed();
    }

    /**
     * @param Subscription $subscription
     *
     * @return bool
     */
    private function hasInvoicePaymentFailedEventForSubscription(Subscription $subscription)
    {
        $paymentFailedEvents = $this->stripeEventService->getForUserAndType(
            $this->event->getEntity()->getUser(),
            'invoice.payment_failed'
        );

        foreach ($paymentFailedEvents as $paymentFailedEvent) {
            /* @var Invoice $invoice */
            $invoice = $paymentFailedEvent->getStripeEventObject()->getDataObject()->getObject();

            if ($invoice->isForSubscription($subscription)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function hasTrialToActiveStatusChangeEvent()
    {
        return !is_null($this->getMostRecentTrialToActiveStatusChangeEvent());
    }

    /**
     * @return Event
     */
    private function getMostRecentTrialToActiveStatusChangeEvent()
    {
        $customerSubscriptionUpdatedEvents = $this->stripeEventService->getForUserAndType(
            $this->event->getEntity()->getUser(),
            'customer.subscription.updated'
        );

        foreach ($customerSubscriptionUpdatedEvents as $customerSubscriptionUpdatedEvent) {
            /* @var CustomerSubscriptionUpdated $stripeCustomerSubscriptionUpdatedEvent */
            $stripeCustomerSubscriptionUpdatedEvent = $customerSubscriptionUpdatedEvent->getStripeEventObject();

            if ($stripeCustomerSubscriptionUpdatedEvent->hasStatusChange('trialing:active')) {
                return $customerSubscriptionUpdatedEvent->getStripeEventObject();
            }
        }

        return null;
    }

    /**
     * @return UserAccountPlan
     */
    protected function getUserAccountPlanFromEvent()
    {
        return $this->userAccountPlanService->getForUser($this->event->getEntity()->getUser());
    }

    protected function downgradeToBasicPlan()
    {
        $this->userAccountPlanService->subscribe(
            $this->event->getEntity()->getUser(),
            $this->accountPlanService->find('basic')
        );
    }
}
