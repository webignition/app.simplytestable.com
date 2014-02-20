<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange;

abstract class ActionStatusTest extends StatusChangeTest {   
    
    abstract protected function getExpectedWebClientEventBody();
    
    public function testWebClientEventBody() {        
        $this->assertEquals(
                $this->getExpectedWebClientEventBody(),
                (string)$this->getHttpClientService()->getHistoryPlugin()->getLastRequest()->getPostFields()
        );
    }    
    
    public function testWebClientSubscriberResponseStatusCode() {        
        $this->assertEquals(
                200,
                $this->getHttpClientService()->getHistoryPlugin()->getLastResponse()->getStatusCode()
        );
    } 
}
