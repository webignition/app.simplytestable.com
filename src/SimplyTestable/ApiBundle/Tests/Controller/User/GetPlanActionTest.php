<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class GetPlanActionTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }    

    public function testForUserWithBasicPlan() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);        
        $this->getUserService()->setUser($user);

        $responseObject = json_decode($this->getUserController('getPlanAction')->getPlanAction()->getContent());

        $this->assertEquals('basic', $responseObject->name);         
    }
    
    public function testForUserWithPremiumPlan() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);        
        $this->getUserService()->setUser($user);
        
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->getUserController('getPlanAction')->getPlanAction()->getContent());
        
        $this->assertEquals('personal', $responseObject->name);
        $this->assertEquals('month', $responseObject->summary->interval);
        $this->assertEquals(900, $responseObject->summary->amount);
        $this->assertEquals(5000, $responseObject->credits->limit);
        $this->assertEquals(0, $responseObject->credits->used);
        $this->assertEquals(50, $responseObject->urls_per_job);
        
        $this->assertEquals('trialing', $responseObject->summary->status);        
        $this->assertEquals(30, $responseObject->summary->trial_period_days);        
        $this->assertInternalType('int', $responseObject->summary->current_period_end);
        $this->assertInternalType('int', $responseObject->summary->trial_end);       
    }   
    
    public function testRetrieveForUserWhereIsActiveIsZero() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);        
        $this->getUserService()->setUser($user);
        
        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->getUserController('getPlanAction')->getPlanAction()->getContent());

        $this->assertEquals('basic', $responseObject->name);             
    }
    
    public function testRetrieveForUserWhereIsActiveIsZeroAndUserHasMany() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);        
        $this->getUserService()->setUser($user);
        
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('agency'));
        
        $this->getUserAccountPlanService()->deactivateAllForUser($user);

        $responseObject = json_decode($this->getUserController('getPlanAction')->getPlanAction()->getContent());

        $this->assertEquals('agency', $responseObject->name);             
    }    
}


