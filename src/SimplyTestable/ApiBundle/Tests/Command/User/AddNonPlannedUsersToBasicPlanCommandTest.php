<?php

namespace SimplyTestable\ApiBundle\Tests\Command\User;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class AddNonPlannedUsersToBasicPlanCommandTest extends BaseSimplyTestableTestCase {

    public function testAssignInMaintenanceReadOnlyModeReturnsStatusCode1() {
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));         
        $this->assertEquals(1, $this->runConsole('simplytestable:user:add-non-planned-users-to-basic-plan'));
    }    
    
    public function testPublicUserIsNotAssignedBasicPlan() {
        $this->assertEquals(0, $this->runConsole('simplytestable:user:add-non-planned-users-to-basic-plan'));
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getPublicUser());
        $this->assertEquals('public', $userAccountPlan->getPlan()->getName());
    }    
    
    public function testAdminUserIsNotAssignedBasicPlan() {
        $this->assertEquals(0, $this->runConsole('simplytestable:user:add-non-planned-users-to-basic-plan'));
        $this->assertNull($this->getUserAccountPlanService()->getForUser($this->getUserService()->getAdminUser()));
    }
    
    
    public function testRegularUsersWithoutPlansAreAssignedTheBasicPlanWhenNoUsersHavePlans() {        
        $userEmailAddresses = array(
            'user1@example.com',
            'user2@example.com',
            'user3@example.com'
        );
        
        $users = array();
        
        foreach ($userEmailAddresses as $userEmailAddress) {
            $this->createUser($userEmailAddress, 'password');
            $user = $this->getUserService()->findUserByEmail($userEmailAddress);
            
            $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);            
            $this->getEntityManager()->remove($userAccountPlan);
            $this->getEntityManager()->flush();
            
            $users[] = $user;
        }
        
        foreach ($users as $user) {      
            $this->assertNull($this->getUserAccountPlanService()->getForUser($user));        
        }
        
        $this->assertEquals(0, $this->runConsole('simplytestable:user:add-non-planned-users-to-basic-plan'));
        
        foreach ($users as $user) {            
            $this->assertEquals('basic', $this->getUserAccountPlanService()->getForUser($user)->getPlan()->getName());
        }      
    }
    
    
    public function testRegularUsersWithoutPlansAreAssignedTheBasicPlanWhenSomeUsersHavePlans() {
        $this->createUser('user1@example.com', 'password');
        $user1 = $this->getUserService()->findUserByEmail('user1@example.com');
        
        $fooPlan = $this->createAccountPlan('foo-plan');
        
        $this->getUserAccountPlanService()->modify($user1, $fooPlan);        
        
        $userEmailAddresses = array(
            'user2@example.com',
            'user3@example.com'
        );
        
        $users = array();
        
        foreach ($userEmailAddresses as $userEmailAddress) {
            $this->createUser($userEmailAddress, 'password');
            $user = $this->getUserService()->findUserByEmail($userEmailAddress);
            
            $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);            
            $this->getEntityManager()->remove($userAccountPlan);
            $this->getEntityManager()->flush();
            
            $users[] = $user;
        }
        
        foreach ($users as $user) {
            $this->assertNull($this->getUserAccountPlanService()->getForUser($user));        
        }
        
        $this->assertEquals(0, $this->runConsole('simplytestable:user:add-non-planned-users-to-basic-plan'));
        
        foreach ($users as $user) {            
            $this->assertEquals('basic', $this->getUserAccountPlanService()->getForUser($user)->getPlan()->getName());
        }      
        
        $this->assertEquals('foo-plan', $this->getUserAccountPlanService()->getForUser($user1)->getPlan()->getName());
    }    


}
