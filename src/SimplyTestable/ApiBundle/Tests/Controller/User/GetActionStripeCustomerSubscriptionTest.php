<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class GetActionStripeCustomerSubscriptionTest extends BaseControllerJsonTestCase {
    
    const DEFAULT_TRIAL_PERIOD = 30;
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
    public function testForUserWithPremiumPlan() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);        
        $this->getUserService()->setUser($user);
        
        $userAccountPlan = $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertTrue(isset($responseObject->stripe_customer->subscription));
        $this->assertTrue(isset($responseObject->stripe_customer->subscription->plan));
        
        $this->assertEquals('trialing', $responseObject->stripe_customer->subscription->status);
        $this->assertEquals($userAccountPlan->getStripeCustomer(), $responseObject->stripe_customer->subscription->customer);        
    }    
}


