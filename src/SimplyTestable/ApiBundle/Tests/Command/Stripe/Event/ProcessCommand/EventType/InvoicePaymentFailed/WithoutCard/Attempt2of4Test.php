<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed\WithoutCard;

class Attempt2of4Test extends NonFinalAttemptTest {
    
    protected function getAttemptCount() {
        return 2;
    }
}
