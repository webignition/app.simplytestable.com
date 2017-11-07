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
use webignition\Model\Stripe\Event\Data as StripeEventData;
use webignition\Model\Stripe\Subscription as StripeSubscriptionModel;

class CustomerSubscriptionUpdatedListener extends AbstractCustomerSubscriptionListener
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
    public function onCustomerSubscriptionUpdated(DispatchableEvent $event)
    {
        $this->setEvent($event);

        /* @var $stripeEventObject CustomerSubscriptionUpdated */
        $stripeEventObject = $this->event->getEntity()->getStripeEventObject();
        $stripeSubscription = $this->getStripeSubscription();
        $webClientEventData = array_merge($this->getDefaultWebClientData(), [
            'currency' => $stripeSubscription->getPlan()->getCurrency()
        ]);

        $user = $this->event->getEntity()->getUser();

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

            $userAccountPlan = $this->userAccountPlanService->getForUser($user);
            $stripeCustomer = $this->stripeService->getCustomer($userAccountPlan);

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
                $basicPlan = $this->accountPlanService->getBasicPlan();
                $this->userAccountPlanService->subscribe($user, $basicPlan);
            }

            $this->issueWebClientEvent($webClientEventData);
            $this->markEntityProcessed();
        }
    }
}
