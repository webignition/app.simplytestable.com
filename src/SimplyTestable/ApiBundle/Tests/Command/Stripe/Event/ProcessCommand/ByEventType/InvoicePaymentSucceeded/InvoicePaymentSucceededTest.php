<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\InvoicePaymentSucceeded;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\ByEventTypeTest;

abstract class InvoicePaymentSucceededTest extends ByEventTypeTest {    
    
    abstract protected function getTotal();   
    
    protected function getExpectedNotificationBodyEventName() {
        return 'invoice.payment_succeeded';
    }
    
    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/../StripeEvents/invoice.payment_succeeded.json'
        );
    }      
    
    protected function getFixtureReplacements() {
        $fixtureReplacements = parent::getFixtureReplacements();
        $fixtureReplacements['99.99'] = $this->getTotal();
        
        return $fixtureReplacements;
    }     
}
