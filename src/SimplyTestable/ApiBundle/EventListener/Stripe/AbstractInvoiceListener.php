<?php

namespace SimplyTestable\ApiBundle\EventListener\Stripe;

use SimplyTestable\ApiBundle\Model\Stripe\Invoice\Invoice;

abstract class AbstractInvoiceListener extends AbstractListener
{
    /**
     * @return Invoice
     */
    protected function getStripeInvoice()
    {
        return new Invoice(
            json_encode($this->getEventEntity()->getStripeEventObject()->getDataObject()->getObject()->__toArray())
        );
    }
}
