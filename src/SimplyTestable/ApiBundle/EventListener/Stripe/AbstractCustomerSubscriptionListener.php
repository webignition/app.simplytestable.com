<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use webignition\Model\Stripe\Subscription as StripeSubscriptionModel;
use webignition\Model\Stripe\Discount as StripeDiscountModel;
use webignition\Model\Stripe\Event\Customer\Updated as StripeCustomerUpdatedEvent;
use webignition\Model\Stripe\Customer as StripeCustomerModel;

abstract class AbstractCustomerSubscriptionListener extends AbstractListener
{
    /**
     * @var StripeService
     */
    private $stripeService;

    /**
     * @var UserAccountPlanService
     */
    protected $userAccountPlanService;

    /**
     * @param StripeEventService $stripeEventService
     * @param HttpClientService $httpClientService
     * @param $webClientProperties
     * @param StripeService $stripeService
     * @param UserAccountPlanService $userAccountPlanService
     */
    public function __construct(
        StripeEventService $stripeEventService,
        HttpClientService $httpClientService,
        $webClientProperties,
        StripeService $stripeService,
        UserAccountPlanService $userAccountPlanService
    ) {
        parent::__construct(
            $stripeEventService,
            $httpClientService,
            $webClientProperties
        );

        $this->stripeService = $stripeService;
        $this->userAccountPlanService = $userAccountPlanService;
    }

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

    /**
     * @return UserAccountPlan
     */
    protected function getUserAccountPlanFromEvent()
    {
        return $this->userAccountPlanService->getForUser($this->event->getEntity()->getUser());
    }

    /**
     * @return StripeCustomerModel
     */
    protected function getStripeCustomer()
    {
        return $this->stripeService->getCustomer($this->getUserAccountPlanFromEvent());
    }
}
