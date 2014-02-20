<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed\WithCard;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed\InvoicePaymentFailedTest;

abstract class WithCardTest extends InvoicePaymentFailedTest {   
    
    protected function getStripeServiceResponseMethod() {
        return 'getCustomer';
    }
    
    protected function getStripeServiceResponseData() {
        return array(
            'active_card' => '123'
        );
    }

    protected function getHasCard() {
        return true;
    }
}
