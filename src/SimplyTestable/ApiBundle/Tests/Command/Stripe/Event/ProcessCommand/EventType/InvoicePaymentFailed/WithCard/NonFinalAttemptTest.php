<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed\WithCard;

abstract class NonFinalAttemptTest extends WithCardTest {

    protected function getIsClosed() {
        return false;
    }
    
    protected function getNextPaymentAttempt() {
        return '1382458680';
    }
}
