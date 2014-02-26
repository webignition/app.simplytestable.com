<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\InvoicePaymentSucceeded;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\SingleEventTest;

abstract class InvoicePaymentSucceededTest extends SingleEventTest {    
    
    abstract protected function getTotal();   
    
    protected function getExpectedNotificationBodyEventName() {
        return 'invoice.payment_succeeded';
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
