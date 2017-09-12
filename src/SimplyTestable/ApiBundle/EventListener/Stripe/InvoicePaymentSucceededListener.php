<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;

class InvoicePaymentSucceededListener extends InvoiceListener
{
    /**
     * @param DispatchableEvent $event
     */
    public function onInvoicePaymentSucceeded(DispatchableEvent $event)
    {
        $this->setEvent($event);

        $invoice = $this->getStripeInvoice();

        if ($invoice->getTotal() === 0 && $invoice->getAmountDue() === 0) {
            $this->markEntityProcessed();

            return;
        }

        $webClientEventData = [
            'lines' => $invoice->getLinesSummary(),
            'subtotal' => (string)$invoice->getSubtotal(),
            'total' => (string)$invoice->getTotal(),
            'amount_due' => (string)$invoice->getAmountDue(),
            'invoice_id' => $invoice->getId(),
            'has_discount' => (int)$invoice->hasDiscount(),
            'currency' => $invoice->getCurrency()
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
