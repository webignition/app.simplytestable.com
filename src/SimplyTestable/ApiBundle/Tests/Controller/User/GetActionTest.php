<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class GetActionTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }    

    public function testGetForUserWithBasicPlan() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);        
        $this->getUserService()->setUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        
        $this->assertEquals($email, $responseObject->email);
        $this->assertEquals('basic', $responseObject->plan->name);         
    }   
    
    public function testGetForUserWithPremiumPlan() {
        $email = 'user1@example.com';
        $password = 'password1';
        
        $user = $this->createAndFindUser($email, $password);        
        $this->getUserService()->setUser($user);
        
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        
        $this->assertEquals($email, $responseObject->email);
        $this->assertEquals('personal', $responseObject->plan->name);
        $this->assertEquals('month', $responseObject->plan->interval);
        $this->assertEquals(900, $responseObject->plan->amount);         
    }    
}


