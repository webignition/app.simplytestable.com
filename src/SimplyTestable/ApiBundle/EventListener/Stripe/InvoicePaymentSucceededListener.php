<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

class InvoicePaymentSucceededListener extends Listener
{
    /**
     * 
     * getStripeInvoice
     * 
     * getDefaultWebClientData
     * issueWebClientEvent
     * markEntityProcessed
     */    
    
    public function onInvoicePaymentSucceeded(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $this->setEvent($event);
        
        $invoice = $this->getStripeInvoice();
        
        if ($invoice->getTotal() === 0 && $invoice->getAmountDue() === 0) {
            $this->markEntityProcessed();
            return;
        }
        
        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), array(
            'lines' => $invoice->getLinesSummary(),
            'total' => $invoice->getTotal(),
            'amount_due' => $invoice->getAmountDue(),
            'invoice_id' => $invoice->getId()
        )));
        
        $this->markEntityProcessed();        
    }  
}