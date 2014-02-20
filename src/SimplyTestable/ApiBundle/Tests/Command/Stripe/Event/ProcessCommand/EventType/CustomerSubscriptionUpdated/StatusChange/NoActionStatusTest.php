<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange;

abstract class NoActionStatusTest extends StatusChangeTest {   
    
    public function testNoWebClientRequestIsMade() {
        $this->assertEquals(0, $this->getHttpClientService()->getHistoryPlugin()->count());
    } 
}
