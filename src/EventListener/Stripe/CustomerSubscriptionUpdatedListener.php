<?php

namespace App\EventListener\Stripe;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as HttpClient;
use App\Event\Stripe\DispatchableEvent;
use App\Services\AccountPlanService;
use App\Services\StripeEventService;
use App\Services\StripeService;
use App\Services\UserAccountPlanService;
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
     * @param HttpClient $httpClient
     * @param EntityManagerInterface $entityManager
     * @param string $webClientStripeWebHookUrl
     * @param StripeService $stripeService
     * @param UserAccountPlanService $userAccountPlanService
     * @param AccountPlanService $accountPlanService
     */
    public function __construct(
        StripeEventService $stripeEventService,
        HttpClient $httpClient,
        EntityManagerInterface $entityManager,
        $webClientStripeWebHookUrl,
        StripeService $stripeService,
        UserAccountPlanService $userAccountPlanService,
        AccountPlanService $accountPlanService
    ) {
        parent::__construct(
            $stripeEventService,
            $httpClient,
            $entityManager,
            $webClientStripeWebHookUrl,
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
