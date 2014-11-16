<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;

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
            'amount_due' => $invoice->getAmountDue(),
            'currency' => $invoice->getCurrency()
        ));
        
        $this->issueWebClientEvent($webClientData);       
        $this->markEntityProcessed();
        
        if ($this->hasRelatedCustomerSubscriptionDeletedEvent($invoice)) {
            $eventEntity = $this->getCustomerSubscriptionDeletedEvent($invoice);            
            $this->dispatcher->dispatch(
                    'stripe_process.' . $eventEntity->getType(),
                    new DispatchableEvent($eventEntity)
            );
        }
    }
    
    
    private function getCustomerSubscriptionDeletedEvent(\SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice $invoice) {
        $subscriptionLineItems = $invoice->getSubscriptionLines();
        if (count($subscriptionLineItems) == 0) {
        return null;  
        }
        
        $subscriptionDeletedEvents = $this->getStripeEventService()->getForUserAndType(
            $this->getEventEntity()->getUser(),
            'customer.subscription.deleted'
        );
        
        if (count($subscriptionDeletedEvents) == 0) {
        return null;  
        }
        
        
        foreach ($subscriptionDeletedEvents as $subscriptionDeletedEvent) {
            /* @var $eventSubscription \webignition\Model\Stripe\Subscription */
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
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice $invoice
     * @return boolean
     */
    private function hasRelatedCustomerSubscriptionDeletedEvent(\SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice $invoice) {
        return !is_null($this->getCustomerSubscriptionDeletedEvent($invoice));
    }
}