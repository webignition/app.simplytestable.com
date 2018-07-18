<?php

namespace App\EventListener\Stripe;

use App\Model\Stripe\Invoice\Invoice;

abstract class AbstractInvoiceListener extends AbstractListener
{
    /**
     * @return Invoice
     */
    protected function getStripeInvoice()
    {
        return new Invoice(
            json_encode($this->event->getEntity()->getStripeEventObject()->getDataObject()->getObject()->__toArray())
        );
    }
}
