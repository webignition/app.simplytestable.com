<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

class InvoicePaymentFailedListener extends InvoiceListener
{

    public function onInvoicePaymentFailed(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
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
            'amount_due' => $invoice->getAmountDue()
        ));
        
        $this->issueWebClientEvent($webClientData);       
        $this->markEntityProcessed();
        
        if ($this->invoiceSubscriptionHasRelatedCustomerSubscriptionDeletedEvent($invoice)) {
            $this->downgradeToBasicPlan();
        }
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice $invoice
     * @return boolean
     */
    private function invoiceSubscriptionHasRelatedCustomerSubscriptionDeletedEvent(\SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice $invoice) {
        $subscriptionLineItems = $invoice->getSubscriptionLines();
        if (count($subscriptionLineItems) == 0) {
            return false;
        }
        
        $subscriptionDeletedEvents = $this->getStripeEventService()->getForUserAndType(
            $this->getEventEntity()->getUser(),
            'customer.subscription.deleted'
        );
        
        if (count($subscriptionDeletedEvents) == 0) {
            return false;
        }
        
        
        foreach ($subscriptionDeletedEvents as $subscriptionDeletedEvent) {
            /* @var $eventSubscription \webignition\Model\Stripe\Subscription */
            $deletedEventSubscription = $subscriptionDeletedEvent->getStripeEventObject()->getDataObject()->getObject();
            
            foreach ($subscriptionLineItems as $lineItem) {
                /* @var $lineItem \webignition\Model\Stripe\Invoice\LineItem\Subscription */
                if ($deletedEventSubscription->getId() == $lineItem->getId()) {
                    return true;
                }
            }
        }
        
        return false;
    }
}