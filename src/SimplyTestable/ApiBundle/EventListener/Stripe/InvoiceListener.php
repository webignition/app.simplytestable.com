<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

abstract class InvoiceListener extends Listener
{
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice
     */
    protected function getStripeInvoice() {
        return new \SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice(json_encode($this->getEventEntity()->getStripeEventObject()->getDataObject()->getObject()->__toArray()));
    }
}