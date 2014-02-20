<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed\WithoutCard;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed\InvoicePaymentFailedTest;

abstract class WithoutCardTest extends InvoicePaymentFailedTest {   

    protected function getHasCard() {
        return false;
    }
}
