<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

class InvoicePaymentSucceededListener extends InvoiceListener
{
    
    public function onInvoicePaymentSucceeded(\SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent $event) {
        $this->setEvent($event);
        
        $invoice = $this->getStripeInvoice();
        
        if ($invoice->getTotal() === 0 && $invoice->getAmountDue() === 0) {
            $this->markEntityProcessed();
            return;
        }

        $webClientEventData = [
            'lines' => $invoice->getLinesSummary(),
            'total' => $invoice->getTotal(),
            'amount_due' => $invoice->getAmountDue(),
            'invoice_id' => $invoice->getId()
        ];

        if ($invoice->hasDiscount()) {
            $discount = $invoice->getSubtotal() * ($invoice->getDiscount()->getCoupon()->getPercentOff() / 100);

            $webClientEventData['discount'] = [
                'coupon' => $invoice->getDiscount()->getCoupon()->getId(),
                'percent_off' => $invoice->getDiscount()->getCoupon()->getPercentOff(),
                'discount' => $discount
            ];
        }

        $this->issueWebClientEvent(array_merge($this->getDefaultWebClientData(), $webClientEventData));
        
        $this->markEntityProcessed();        
    }  
}