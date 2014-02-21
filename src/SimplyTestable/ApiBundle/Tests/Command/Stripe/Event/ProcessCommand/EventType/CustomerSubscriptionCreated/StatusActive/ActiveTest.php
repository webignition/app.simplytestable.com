<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionCreated\StatusActive;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionCreated\CustomerSubscriptionCreatedTest;

abstract class ActiveTest extends CustomerSubscriptionCreatedTest {
    
    public function testWebClientEventBody() {        
        $this->assertEquals(
                'event=customer.subscription.created&user=user%40example.com&status='.$this->getSubscriptionStatus().'&has_card='.((int)$this->getHasCard()).'&plan_name=Basic&current_period_start=1392757613&current_period_end=1395176813',
                (string)$this->getHttpClientService()->getHistoryPlugin()->getLastRequest()->getPostFields()
        );
    } 
    
    public function testWebClientSubscriberResponseStatusCode() {        
        $this->assertEquals(
                200,
                $this->getHttpClientService()->getHistoryPlugin()->getLastResponse()->getStatusCode()
        );
    }    
    
    protected function getSubscriptionStatus() {
        return 'active';
    }

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../../StripeEvents/customer.subscription.created.active.json';
    }
}
