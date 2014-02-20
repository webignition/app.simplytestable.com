<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoiceCreated;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\EventTypeTest;

abstract class InvoiceCreatedTest extends EventTypeTest {   
    
    abstract protected function getTotal();
    
    protected function getHttpFixtureItems() {
        return array(
            "HTTP/1.1 200 OK"
        );
    }
    
    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../../StripeEvents/invoice.created.json';
    }     
    
    protected function getFixtureReplacements() {
        $fixtureReplacements = parent::getFixtureReplacements();
        $fixtureReplacements['99.99'] = $this->getTotal();
        
        return $fixtureReplacements;
    }     
}
