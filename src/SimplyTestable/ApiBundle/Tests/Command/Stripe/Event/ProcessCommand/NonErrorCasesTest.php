<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand;

abstract class NonErrorCasesTest extends ProcessCommandTest {
    
    protected function getExpectedNotificationBodyFields() {
        return array(
            'event' => $this->getExpectedNotificationBodyEventName(),
            'user' => 'user@example.com'            
        );
    }
    
    abstract protected function getExpectedNotificationBodyEventName();

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
    
}
