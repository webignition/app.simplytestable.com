<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use webignition\Model\Stripe\Subscription as StripeSubscriptionModel;
use webignition\Model\Stripe\Customer as StripeCustomerModel;

class InvoicePaymentFailedListener extends AbstractInvoiceListener
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var StripeService
     */
    private $stripeService;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @param StripeService $stripeService
     * @param StripeEventService $stripeEventService
     * @param UserAccountPlanService $userAccountPlanService
     * @param HttpClientService $httpClientService
     * @param $webClientProperties
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        StripeEventService $stripeEventService,
        HttpClientService $httpClientService,
        $webClientProperties,
        StripeService $stripeService,
        UserAccountPlanService $userAccountPlanService,
        EventDispatcherInterface $dispatcher
    ) {
        parent::__construct(
            $stripeEventService,
            $httpClientService,
            $webClientProperties
        );

        $this->stripeService = $stripeService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param DispatchableEvent $event
     */
    public function onInvoicePaymentFailed(DispatchableEvent $event)
    {
        $this->setEvent($event);

        $user = $this->event->getEntity()->getUser();
        $userAccountPlan = $this->userAccountPlanService->getForUser($user);
        $stripeCustomer = $this->stripeService->getCustomer($userAccountPlan);

        if ($stripeCustomer->hasCard() === false) {
            $this->markEntityProcessed();

            return;
        }

        $invoice = $this->getStripeInvoice();

        $webClientData = array_merge($this->getDefaultWebClientData(), [
            'lines' => $invoice->getLinesSummary(),
            'invoice_id' => $invoice->getId(),
            'total' => $invoice->getTotal(),
            'amount_due' => $invoice->getAmountDue(),
            'currency' => $invoice->getCurrency()
        ]);

        $this->issueWebClientEvent($webClientData);
        $this->markEntityProcessed();

        $customerSubscriptionDeletedEvent = $this->getCustomerSubscriptionDeletedEvent($invoice);

        if (!empty($customerSubscriptionDeletedEvent)) {
            $this->dispatcher->dispatch(
                'stripe_process.' . $customerSubscriptionDeletedEvent->getType(),
                new DispatchableEvent($customerSubscriptionDeletedEvent)
            );
        }
    }

    /**
     * @param Invoice $invoice
     *
     * @return StripeEvent|null
     */
    private function getCustomerSubscriptionDeletedEvent(Invoice $invoice)
    {
        $subscriptionLineItems = $invoice->getSubscriptionLines();
        if (count($subscriptionLineItems) == 0) {
            return null;
        }

        $user = $this->event->getEntity()->getUser();

        $subscriptionDeletedEvents = $this->stripeEventService->getForUserAndType(
            $user,
            'customer.subscription.deleted'
        );

        if (count($subscriptionDeletedEvents) == 0) {
            return null;
        }

        foreach ($subscriptionDeletedEvents as $subscriptionDeletedEvent) {
            /* @var StripeSubscriptionModel $deletedEventSubscription */
            $deletedEventSubscription = $subscriptionDeletedEvent->getStripeEventObject()->getDataObject()->getObject();

            foreach ($subscriptionLineItems as $lineItem) {
                /* @var $lineItem \webignition\Model\Stripe\Invoice\LineItem\Subscription */
                if ($deletedEventSubscription->getId() == $lineItem->getId()) {
                    return $subscriptionDeletedEvent;
                }
            }
        }

        return null;
    }
}
