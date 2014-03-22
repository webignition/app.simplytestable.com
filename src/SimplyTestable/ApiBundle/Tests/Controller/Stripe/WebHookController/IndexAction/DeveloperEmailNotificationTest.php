<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Stripe\WebHookController\IndexAction;

class DeveloperEmailNotificationTest extends IndexActionTest {
    
    private $fixture = null;
    
    public function setUp() {
        parent::setUp();
        
        $this->fixture = $this->getFixture($this->getFixturesDataPath(). '/StripeEvents/invoice.payment_failed.event.json');                
        
        $this->getStripeWebHookController('indexAction', array(
            'event' => $this->fixture
        ))->indexAction();    
    }
    
    
    
    public function testSingleEmailIsSent() {        
        $this->assertEquals(1, $this->getMailService()->getSender()->getHistory()->count());    
    }
    

    public function testEmailSendDidNotGenerateError() {
        $this->assertFalse($this->getMailService()->getSender()->getLastResponse()->isError());              
    }    
    
    public function testMessageBodyContainsEvent() {              
        $this->assertNotificationMessageContains($this->fixture);        
    }    
    
    private function assertNotificationMessageContains($value) {
        $lastMessage = $this->getMailService()->getSender()->getLastMessage();
        $refObject = new \ReflectionObject($lastMessage);
        $refProperty = $refObject->getProperty('textMessage');
        $refProperty->setAccessible(true);
        
        $this->assertTrue(substr_count($refProperty->getValue($lastMessage), $value) > 0, 'Notification message does not contain "'.$value.'"');
    }     
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\Mail\Service
     */
    private function getMailService() {
        return $this->container->get('simplytestable.services.mail.service');
    }      

}


