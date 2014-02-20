<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed\WithCard;

class Attempt3of4Test extends NonFinalAttemptTest {
    
    protected function getAttemptCount() {
        return 3;
    }
}
