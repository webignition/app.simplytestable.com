<?php

namespace App\EventListener\Stripe;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as HttpClient;
use App\Services\StripeEventService;
use App\Services\StripeService;
use App\Services\UserAccountPlanService;
use webignition\Model\Stripe\Subscription as StripeSubscriptionModel;
use webignition\Model\Stripe\Discount as StripeDiscountModel;
use webignition\Model\Stripe\Event\Customer\Updated as StripeCustomerUpdatedEvent;

abstract class AbstractCustomerSubscriptionListener extends AbstractListener
{
    /**
     * @var StripeService
     */
    protected $stripeService;

    /**
     * @var UserAccountPlanService
     */
    protected $userAccountPlanService;

    /**
     * @param StripeEventService $stripeEventService
     * @param HttpClient $httpClient
     * @param EntityManagerInterface $entityManager
     * @param string $webClientStripeWebHookUrl
     * @param StripeService $stripeService
     * @param UserAccountPlanService $userAccountPlanService
     */
    public function __construct(
        StripeEventService $stripeEventService,
        HttpClient $httpClient,
        EntityManagerInterface $entityManager,
        $webClientStripeWebHookUrl,
        StripeService $stripeService,
        UserAccountPlanService $userAccountPlanService
    ) {
        parent::__construct(
            $stripeEventService,
            $httpClient,
            $entityManager,
            $webClientStripeWebHookUrl
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
        $user = $this->event->getEntity()->getUser();

        $events = $this->stripeEventService->getForUserAndType(
            $user,
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
