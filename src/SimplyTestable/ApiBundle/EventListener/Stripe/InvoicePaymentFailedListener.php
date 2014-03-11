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
    }
}