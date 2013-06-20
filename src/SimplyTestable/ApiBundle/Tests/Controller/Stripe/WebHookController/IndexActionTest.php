<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Stripe\WebHookController;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class IndexActionTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }  
    
    
    public function testWithNoRequestBody() {
        $this->assertEquals(400, $this->getStripeWebHookController('indexAction')->indexAction()->getStatusCode());
    }
    
    
    public function testWithNoStripeEvent() {
        $this->assertEquals(400, $this->getStripeWebHookController('indexAction', array(
            'event' => 'eventdata but this is not a stripe JSON object by any means'
        ))->indexAction()->getStatusCode());
    }
    
    public function testWithStripeEvent() {
        $fixture = $this->getFixture($this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/event.json');        
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();        
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseObject = json_decode($response->getContent());
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
        
        $this->assertEquals($responseObject->stripe_id, $stripeEvent->getStripeId());
        
    }

}


