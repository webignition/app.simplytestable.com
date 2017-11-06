<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use webignition\Model\Stripe\Subscription as StripeSubscriptionModel;

class InvoicePaymentFailedListener extends AbstractInvoiceListener
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param StripeService $stripeService
     * @param StripeEventService $stripeEventService
     * @param UserAccountPlanService $userAccountPlanService
     * @param HttpClientService $httpClientService
     * @param $webClientProperties
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        StripeService $stripeService,
        StripeEventService $stripeEventService,
        UserAccountPlanService $userAccountPlanService,
        HttpClientService $httpClientService,
        $webClientProperties,
        EventDispatcherInterface $dispatcher
    ) {
        parent::__construct(
            $stripeService,
            $stripeEventService,
            $userAccountPlanService,
            $httpClientService,
            $webClientProperties
        );

        $this->dispatcher = $dispatcher;
    }

    /**
     * @param DispatchableEvent $event
     */
    public function onInvoicePaymentFailed(DispatchableEvent $event)
    {
        $this->setEvent($event);

        if ($this->getStripeCustomer()->hasCard() === false) {
            $this->markEntityProcessed();

            return;
        }

        $invoice = $this->getStripeInvoice();

        $webClientData = array_merge($this->getDefaultWebClientData(), array(
            'lines' => $invoice->getLinesSummary(),
            'invoice_id' => $invoice->getId(),
            'total' => $invoice->getTotal(),
            'amount_due' => $invoice->getAmountDue(),
            'currency' => $invoice->getCurrency()
        ));

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

        $subscriptionDeletedEvents = $this->stripeEventService->getForUserAndType(
            $this->event->getEntity()->getUser(),
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
