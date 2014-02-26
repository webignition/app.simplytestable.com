<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\NonErrorCasesTest;

abstract class SingleEventTest extends NonErrorCasesTest {      
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Stripe\Event
     */
    private $stripeEvent;
    
    public function setUp() {
        parent::setUp();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.1 200 OK"
        )));        
        
        $user = $this->getTestUser();        
        $this->getUserService()->setUser($user);
        
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'agency'
        );
        
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $this->getFixtureContent()
        ))->indexAction();
        
        $responseObject = json_decode($response->getContent());
        
        $this->getStripeService()->addResponseData($this->getStripeServiceResponseMethod(), $this->getStripeServiceResponseData());
        
        $this->assertReturnCode(0, array(
            'stripeId' => $responseObject->stripe_id
        ));
        
        $this->stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
    } 
    
    public function testEventIsProcessed() {
        $this->assertTrue($this->stripeEvent->getIsProcessed());        
    }
    
    
    public function testNotificationBodyFields() {
        foreach ($this->getExpectedNotificationBodyFields() as $name => $expectedValue) {            
            $this->assertNotificationBodyField($name, $expectedValue);
        }        
    }
    
    abstract protected function getStripeEventFixturePath();
    
    protected function getStripeServiceResponseMethod() {
        return null;
    }
    
    protected function getStripeServiceResponseData() {
        return array();
    }
    
    
    private function getFixtureContent() {
        $fixtureReplacements = $this->getFixtureReplacements();
        
        return str_replace(
                array_keys($fixtureReplacements),
                array_values($fixtureReplacements),
                $this->getFixture($this->getStripeEventFixturePath())
        );
    }
}
