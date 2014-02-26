<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand;

abstract class NonErrorCasesTest extends ProcessCommandTest {
    
    protected $stripeId;
    protected $stripeEvent;
    
    abstract protected function getExpectedNotificationBodyEventName();
    abstract protected function getStripeEventFixturePaths();
    
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
        
        foreach ($this->getStripeEventFixturePaths() as $fixturePath) {
            $response = $this->getStripeWebHookController('indexAction', array(
                'event' => $this->getFixtureContent($fixturePath)
            ))->indexAction();           
        } 
        
        $responseObject = json_decode($response->getContent());
        $this->stripeId = $responseObject->stripe_id;       
    }
    
    public function testEventIsProcessed() {
        $this->assertTrue($this->stripeEvent->getIsProcessed());        
    }
    
    
    public function testNotificationBodyFields() {
        foreach ($this->getExpectedNotificationBodyFields() as $name => $expectedValue) {            
            $this->assertNotificationBodyField($name, $expectedValue);
        }        
    }    
    
    protected function getExpectedNotificationBodyFields() {
        return array(
            'event' => $this->getExpectedNotificationBodyEventName(),
            'user' => 'user@example.com'            
        );
    }

    protected function getFixtureReplacements() {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getUser()); 
        
        return array(
            '{{stripe_customer}}' => $userAccountPlan->getStripeCustomer()
        );
    }
    
    
    protected function assertNotificationBodyField($name, $expectedValue) {
        /* @var $postFields \Guzzle\Http\QueryString */
        $postFields = $this->getHttpClientService()->getHistoryPlugin()->getLastRequest()->getPostFields();
        
        $this->assertTrue($postFields->hasKey($name), 'Notification body field "'.$name.'" not set');
        $this->assertEquals($expectedValue, $postFields->get($name));
    }
    

    protected function getFixtureContent($path) {
        $fixtureReplacements = $this->getFixtureReplacements();
        
        return str_replace(
                array_keys($fixtureReplacements),
                array_values($fixtureReplacements),
                $this->getFixture($path)
        );
    }    
    
}
