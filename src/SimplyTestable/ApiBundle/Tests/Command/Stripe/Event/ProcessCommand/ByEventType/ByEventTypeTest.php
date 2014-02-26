<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\NonErrorCasesTest;

abstract class ByEventTypeTest extends NonErrorCasesTest {   
    
    public function setUp() {
        parent::setUp();
        
        $this->getStripeService()->addResponseData($this->getStripeServiceResponseMethod(), $this->getStripeServiceResponseData());
        
        $this->assertReturnCode(0, array(
            'stripeId' => $this->stripeId
        ));
        
        $this->stripeEvent = $this->getStripeEventService()->getByStripeId($this->stripeId);
    }
    
    protected function getStripeServiceResponseMethod() {
        return null;
    }
    
    protected function getStripeServiceResponseData() {
        return array();
    }

}
