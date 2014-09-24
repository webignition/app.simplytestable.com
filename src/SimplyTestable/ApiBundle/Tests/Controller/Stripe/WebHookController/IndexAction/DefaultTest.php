<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Stripe\WebHookController\IndexAction;

class DefaultTest extends IndexActionTest {
    
    public function testWithStripeInvoicePaymentFailedEventForUnknownUser() {
        $fixture = $this->getFixture($this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/invoice.payment_failed.event.json');        
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();        
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseObject = json_decode($response->getContent());
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
        
        $this->assertEquals($responseObject->stripe_id, $stripeEvent->getStripeId());        
    }
    
    public function testWithStripeInvoicePaymentFailedEventForKnownUser() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);       
        
        $userAccountPlan = $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));        
        
        $fixture = $this->getFixture($this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/invoice.payment_failed.event.json');        
        $fixtureObject = json_decode($fixture);
        
        $fixtureObject->data->object->customer = $userAccountPlan->getStripeCustomer();

        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => json_encode($fixtureObject)
        ))->indexAction();        
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseObject = json_decode($response->getContent());
        $this->assertEquals($email, $responseObject->user);
        
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);        
        $this->assertEquals($user->getId(), $stripeEvent->getUser()->getId());        
    }    
    
    public function testWithStripeCustomerSubscriptionCreatedEventForUnknownUser() {
        $fixture = $this->getFixture($this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/customer.subscription.created.event.json');        
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();        
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseObject = json_decode($response->getContent());
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
        
        $this->assertEquals($responseObject->stripe_id, $stripeEvent->getStripeId());        
    }   
    
    
    public function testWithStripeCustomerSubscriptionCreatedEventForKnownUser() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);       
        
        $userAccountPlan = $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));        
        
        $fixture = $this->getFixture($this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/customer.subscription.created.event.json');        
        $fixtureObject = json_decode($fixture);
        
        $fixtureObject->data->object->customer = $userAccountPlan->getStripeCustomer();

        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => json_encode($fixtureObject)
        ))->indexAction();        
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseObject = json_decode($response->getContent());
        $this->assertEquals($email, $responseObject->user);
        
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);        
        $this->assertEquals($user->getId(), $stripeEvent->getUser()->getId());  
        
    } 
    
    
    public function testWithStripeInvoiceCreatedEventForUnknownUser() {
        $fixture = $this->getFixture($this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/invoice.created.event.json');        
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();        
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseObject = json_decode($response->getContent());
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
        
        $this->assertEquals($responseObject->stripe_id, $stripeEvent->getStripeId());
        
    }   
    
    
    public function testWithStripeInvoiceCreatedEventForKnownUser() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);       
        
        $userAccountPlan = $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));        
        
        $fixture = $this->getFixture($this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/invoice.created.event.json');        
        $fixtureObject = json_decode($fixture);
        
        $fixtureObject->data->object->customer = $userAccountPlan->getStripeCustomer();

        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => json_encode($fixtureObject)
        ))->indexAction();        
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseObject = json_decode($response->getContent());
        $this->assertEquals($email, $responseObject->user);
        
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);        
        $this->assertEquals($user->getId(), $stripeEvent->getUser()->getId());  
        
    } 
    
    
    public function testWithStripeCustomerUpdatedEventForUnknownUser() {
        $fixture = $this->getFixture($this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/customer.updated.event.json');        
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();        
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseObject = json_decode($response->getContent());
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
        
        $this->assertEquals($responseObject->stripe_id, $stripeEvent->getStripeId());
        
    }   
    
    
    public function testWithStripeCustomerUpdatedEventForKnownUser() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);       
        
        $userAccountPlan = $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));        
        
        $fixture = $this->getFixture($this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/customer.updated.event.json');        
        $fixtureObject = json_decode($fixture);
        
        $fixtureObject->data->object->id = $userAccountPlan->getStripeCustomer();

        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => json_encode($fixtureObject)
        ))->indexAction();        
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseObject = json_decode($response->getContent());
        $this->assertEquals($email, $responseObject->user);
        
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);        
        $this->assertEquals($user->getId(), $stripeEvent->getUser()->getId());
    } 
    
    public function testStripeEventDataIsPersisted() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);
        $this->getUserService()->setUser($user);       
        
        $userAccountPlan = $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));        
        
        $fixture = $this->getFixture($this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/customer.subscription.created.event.json');       
        $fixtureObject = json_decode($fixture);        
        
        $fixtureObject->data->object->customer = $userAccountPlan->getStripeCustomer();    

        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => json_encode($fixtureObject)
        ))->indexAction();        
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseObject = json_decode($response->getContent());
        $this->assertEquals($email, $responseObject->user);
        
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);        
        $this->assertEquals($user->getId(), $stripeEvent->getUser()->getId());         
        $this->assertNotNull($stripeEvent->getStripeEventData());
        
        $this->assertEquals(json_encode($fixtureObject), $stripeEvent->getStripeEventData());
        $this->assertEquals(new \webignition\Model\Stripe\Event\Event(json_encode($fixtureObject)), $stripeEvent->getStripeEventObject());
    }
    
    
    public function testValidEventCreatesProcessEventResqueJob() {
        $fixture = $this->getFixture($this->getFixturesDataPath(__FUNCTION__). '/StripeEvents/invoice.payment_failed.event.json');        
        
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();
        
        $responseObject = json_decode($response->getContent());
        
        $stripeEvent = $this->getStripeEventService()->getByStripeId($responseObject->stripe_id);
        
        $this->assertTrue($this->getResqueQueueService()->contains(
            'stripe-event',
            array(
                'stripeId' => $stripeEvent->getStripeId()
            )
        ));
    }  

}


