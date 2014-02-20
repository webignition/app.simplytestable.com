<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed\WithoutCard;

abstract class NonFinalAttemptTest extends WithoutCardTest {

    protected function getIsClosed() {
        return false;
    }
    
    protected function getNextPaymentAttempt() {
        return '1382458680';
    }
}
