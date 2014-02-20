<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed\WithoutCard;

class Attempt4of4Test extends WithoutCardTest {
    
    protected function getAttemptCount() {
        return 4;
    }

    protected function getIsClosed() {
        return true;
    }
    
    protected function getNextPaymentAttempt() {
        return null;
    }
}
