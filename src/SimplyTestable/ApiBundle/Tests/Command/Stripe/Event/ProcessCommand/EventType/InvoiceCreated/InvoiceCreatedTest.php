<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoiceCreated;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\EventTypeTest;

abstract class InvoiceCreatedTest extends EventTypeTest {   
    
    abstract protected function getTotal();
    abstract protected function getAmountDue();
    
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
        $fixtureReplacements['0.01'] = $this->getTotal();
        $fixtureReplacements['0.02'] = $this->getAmountDue();
        
        return $fixtureReplacements;
    }     
}
