<?php

namespace SimplyTestable\ApiBundle\Tests\Entity;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;

class UserAccountPlanTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8StripeCustomer() {
        $stripeCustomer = 'test-ɸ';
        
        $user = $this->getUserService()->create('user@example.com', 'password');
        
        $plan = $this->createAccountPlan();       
        
        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($plan);
        $userAccountPlan->setStripeCustomer($stripeCustomer);
    
        $this->getEntityManager()->persist($userAccountPlan);
        $this->getEntityManager()->flush();
        
        $userAccountPlanId = $userAccountPlan->getId();
        
        $this->getEntityManager()->clear();
  
        $this->assertEquals($stripeCustomer, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\UserAccountPlan')->find($userAccountPlanId)->getStripeCustomer());
    }
    

    public function testPersist() {
        $user = $this->getUserService()->create('user@example.com', 'password');
        
        $plan = $this->createAccountPlan();       
        
        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($plan);
    
        $this->getEntityManager()->persist($userAccountPlan);
        $this->getEntityManager()->flush();
        
        $this->assertNotNull($userAccountPlan->getId());
    }
    
    public function testApplyOnePlanToMultipleUsers() {
        $user1 = $this->getUserService()->create('user1@example.com', 'password');
        $user2 = $this->getUserService()->create('user2@example.com', 'password');
        
        $plan = $this->createAccountPlan();       
        
        $userAccountPlan1 = new UserAccountPlan();
        $userAccountPlan1->setUser($user1);
        $userAccountPlan1->setPlan($plan);
        
        $userAccountPlan2 = new UserAccountPlan();
        $userAccountPlan2->setUser($user2);
        $userAccountPlan2->setPlan($plan);        
    
        $this->getEntityManager()->persist($userAccountPlan1);
        $this->getEntityManager()->persist($userAccountPlan2);
        $this->getEntityManager()->flush();
        
        $this->assertNotNull($userAccountPlan1->getId());
        $this->assertNotNull($userAccountPlan2->getId());
    }
    
    
    public function testDefaultStartTrialPeriod() {
        $defaultStartTrialPeriod = 30;
        
        $user = $this->getUserService()->create('user@example.com', 'password');
        
        $plan = $this->createAccountPlan();       
        
        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($plan);
        
        $this->assertEquals($defaultStartTrialPeriod, $userAccountPlan->getStartTrialPeriod());
    
        $this->getEntityManager()->persist($userAccountPlan);
        $this->getEntityManager()->flush();
        
        $this->getEntityManager()->clear();
        $this->assertEquals($defaultStartTrialPeriod, $this->getUserAccountPlanService()->getForUser($user)->getStartTrialPeriod());
    }

}
