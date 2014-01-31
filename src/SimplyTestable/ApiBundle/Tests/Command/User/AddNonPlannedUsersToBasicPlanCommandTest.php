<?php

namespace SimplyTestable\ApiBundle\Tests\Command\User;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class AddNonPlannedUsersToBasicPlanCommandTest extends ConsoleCommandTestCase {
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:user:add-non-planned-users-to-basic-plan';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand(),            
            new \SimplyTestable\ApiBundle\Command\User\AddNonPlannedUsersToBasicPlanCommand()
        );
    }    

    public function testAssignInMaintenanceReadOnlyModeReturnsStatusCode1() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');        
        $this->assertReturnCode(1);
    }    
    
    public function testPublicUserIsNotAssignedBasicPlan() {
        $this->assertReturnCode(0);
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getPublicUser());
        $this->assertEquals('public', $userAccountPlan->getPlan()->getName());
    }    
    
    public function testAdminUserIsNotAssignedBasicPlan() {
        $this->assertReturnCode(0);
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
        
        $this->assertReturnCode(0);
        
        foreach ($users as $user) {            
            $this->assertEquals('basic', $this->getUserAccountPlanService()->getForUser($user)->getPlan()->getName());
        }      
    }
    
    
    public function testRegularUsersWithoutPlansAreAssignedTheBasicPlanWhenSomeUsersHavePlans() {
        $this->createUser('user1@example.com', 'password');
        $user1 = $this->getUserService()->findUserByEmail('user1@example.com');
        
        $fooPlan = $this->createAccountPlan('test-foo-plan');
        
        $this->getUserAccountPlanService()->subscribe($user1, $fooPlan);        
        
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
        
        $this->assertReturnCode(0);
        
        foreach ($users as $user) {            
            $this->assertEquals('basic', $this->getUserAccountPlanService()->getForUser($user)->getPlan()->getName());
        }      
        
        $this->assertEquals('test-foo-plan', $this->getUserAccountPlanService()->getForUser($user1)->getPlan()->getName());
    }    


}
