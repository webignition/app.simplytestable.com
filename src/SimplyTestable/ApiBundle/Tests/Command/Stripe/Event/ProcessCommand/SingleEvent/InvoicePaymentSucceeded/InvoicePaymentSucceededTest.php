<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\InvoicePaymentSucceeded;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\SingleEventTest;

abstract class InvoicePaymentSucceededTest extends SingleEventTest {    
    
    abstract protected function getTotal();
    abstract protected function getAmountDue();
    
    protected function getHttpFixtureItems() {
        return array(
            "HTTP/1.1 200 OK"
        );
    }
    
    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../StripeEvents/invoice.payment_succeeded.json';
    }     
    
    protected function getFixtureReplacements() {
        $fixtureReplacements = parent::getFixtureReplacements();
        $fixtureReplacements['99.99'] = $this->getTotal();
        
        return $fixtureReplacements;
    }     
}
