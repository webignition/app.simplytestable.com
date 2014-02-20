<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionCreated\StatusTrialing;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionCreated\CustomerSubscriptionCreatedTest;

abstract class TrialingTest extends CustomerSubscriptionCreatedTest {   
    
    public function testWebClientEventBodyForNoActiveCard() {        
        $this->assertEquals(
                'event=customer.subscription.created&user=user%40example.com&status='.$this->getSubscriptionStatus().'&has_card='.((int)$this->getHasCard()).'&trial_start=1379776581&trial_end=1382368580',
                (string)$this->getHttpClientService()->getHistoryPlugin()->getLastRequest()->getPostFields()
        );
    }     
    
    protected function getSubscriptionStatus() {
        return 'trialing';
    }     

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../../StripeEvents/customer.subscription.created.trialing.json';
    }
}
