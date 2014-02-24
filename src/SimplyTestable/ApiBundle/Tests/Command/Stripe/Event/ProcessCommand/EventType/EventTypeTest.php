<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ProcessCommandTest;

abstract class EventTypeTest extends ProcessCommandTest {      
    
    public function setUp() {
        parent::setUp();
        
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
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureItems()));
        
        $this->getStripeService()->addResponseData($this->getStripeServiceResponseMethod(), $this->getStripeServiceResponseData());
        
        $this->assertReturnCode(0, array(
            'stripeId' => $responseObject->stripe_id
        ));
        
        $this->assertTrue($this->getStripeEventService()->getByStripeId($responseObject->stripe_id)->getIsProcessed());
    }  
    
    protected function assertNotificationBodyField($name, $expectedValue) {
        /* @var $postFields \Guzzle\Http\QueryString */
        $postFields = $this->getHttpClientService()->getHistoryPlugin()->getLastRequest()->getPostFields();
        
//        if ($name === 'lines') {
//            echo (string)$postFields;
//            exit();
////            var_dump($postFields->get('lines'), (string)$postFields->get('lines'));
////            exit();
//        }
        
        $this->assertTrue($postFields->hasKey($name));
        $this->assertEquals($expectedValue, $postFields->get($name));
    }   
    
    abstract protected function getHttpFixtureItems();
    abstract protected function getStripeEventFixturePath();
    
    protected function getStripeServiceResponseMethod() {
        return null;
    }
    
    protected function getStripeServiceResponseData() {
        return array();
    }
    
    protected function getFixtureReplacements() {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getUser()); 
        
        return array(
            '{{stripe_customer}}' => $userAccountPlan->getStripeCustomer()
        );
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
