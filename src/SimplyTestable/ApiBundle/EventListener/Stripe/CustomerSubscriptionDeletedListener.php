<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use Doctrine\ORM\EntityManagerInterface;
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
     * @param StripeEventService $stripeEventService
     * @param HttpClientService $httpClientService
     * @param EntityManagerInterface $entityManager
     * @param StripeService $webClientProperties
     * @param StripeService $stripeService
     * @param UserAccountPlanService $userAccountPlanService
     * @param AccountPlanService $accountPlanService
     */
    public function __construct(
        StripeEventService $stripeEventService,
        HttpClientService $httpClientService,
        EntityManagerInterface $entityManager,
        $webClientProperties,
        StripeService $stripeService,
        UserAccountPlanService $userAccountPlanService,
        AccountPlanService $accountPlanService
    ) {
        parent::__construct(
            $stripeEventService,
            $httpClientService,
            $entityManager,
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
        $user = $this->event->getEntity()->getUser();

        if ($stripeSubscription->wasCancelledDuringTrial()) {
            $userAccountPlan = $this->userAccountPlanService->getForUser($user);

            $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), [
                'plan_name' => $stripeSubscription->getPlan()->getName(),
                'actioned_by' => 'user',
                'is_during_trial' => 1,
                'trial_days_remaining' => $userAccountPlan->getStartTrialPeriod()
            ]));

            $this->markEntityProcessed();

            return;
        }

        if ($this->hasInvoicePaymentFailedEventForSubscription($stripeSubscription)) {
            // System has cancelled following payment failure
            $basicPlan = $this->accountPlanService->getBasicPlan();

            $this->userAccountPlanService->subscribe($user, $basicPlan);
            $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), [
                'plan_name' => $stripeSubscription->getPlan()->getName(),
                'actioned_by' => 'system'
            ]));
            $this->markEntityProcessed();

            return;
        }

        if (!$this->hasTrialToActiveStatusChangeEvent()) {
            // User has canceled after trial
            $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), [
                'plan_name' => $stripeSubscription->getPlan()->getName(),
                'actioned_by' => 'user',
                'is_during_trial' => 0
            ]));
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
        $user = $this->event->getEntity()->getUser();

        $paymentFailedEvents = $this->stripeEventService->getForUserAndType(
            $user,
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
        $user = $this->event->getEntity()->getUser();

        $customerSubscriptionUpdatedEvents = $this->stripeEventService->getForUserAndType(
            $user,
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
}
