<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionTrialWillEnd;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\EventTypeTest;

abstract class CustomerSubscriptionTrialWillEndTest extends EventTypeTest {   
    
    abstract protected function getHasCard();

    public function testWebClientEventBody() {        
        $this->assertEquals(
                'event=customer.subscription.trial_will_end&user=user%40example.com&trial_end=1382368580&has_card='.((int)$this->getHasCard()),
                (string)$this->getHttpClientService()->getHistoryPlugin()->getLastRequest()->getPostFields()
        );
    } 
    
    public function testWebClientSubscriberResponseStatusCode() {        
        $this->assertEquals(
                200,
                $this->getHttpClientService()->getHistoryPlugin()->getLastResponse()->getStatusCode()
        );
    }    

    protected function getHttpFixtureItems() {
        return array(
            "HTTP/1.1 200 OK"
        );
    }

    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.trial_will_end.json';
    }
}
