<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed\WithCard;

class Attempt4of4Test extends WithCardTest {
    
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
