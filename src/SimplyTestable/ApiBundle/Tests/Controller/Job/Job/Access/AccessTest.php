<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\Access;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

abstract class AccessTest extends BaseControllerJsonTestCase {
    
    const CANONICAL_URL = 'http://www.example.com/';
    
    abstract protected function getActionName();

    public function testGetForPublicJobOwnedByNonPublicUserByNonPublicUser() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL, $user->getEmail()));

        $this->getUserService()->setUser($user);
        $this->getJobController('setPublicAction')->setPublicAction(self::CANONICAL_URL, $job->getId());
        
        $this->assertTrue($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());         
        
        $actionName = $this->getActionName();
        
        $this->assertEquals(200, $this->getJobController($actionName, array(
            'user' => $user->getEmail()            
        ))->$actionName(self::CANONICAL_URL, $job->getId())->getStatusCode());        
    }
    
    public function testGetForPublicJobOwnedByNonPublicUserByDifferenNonPublicUser() {
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');
        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL, $user1->getEmail()));

        $this->getUserService()->setUser($user1);
        $this->getJobController('setPublicAction')->setPublicAction(self::CANONICAL_URL, $job->getId());
        
        $this->assertTrue($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $actionName = $this->getActionName();

        $this->getUserService()->setUser($user2);
        $this->assertEquals(200, $this->getJobController($actionName)->$actionName(self::CANONICAL_URL, $job->getId())->getStatusCode());
    }    
    
    public function testGetForPrivateJobOwnedByNonPublicUserByPublicUser() {                
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL, $user->getEmail()));
        
        $this->assertFalse($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $actionName = $this->getActionName();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->assertEquals(403, $this->getJobController($actionName)->$actionName(self::CANONICAL_URL, $job->getId())->getStatusCode());                 
    }    

    
    public function testGetForPrivateJobOwnedByNonPublicUserByNonPublicUser() {      
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL, $user->getEmail()));
        
        $this->assertFalse($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $actionName = $this->getActionName();

        $this->getUserService()->setUser($user);
        $this->assertEquals(200, $this->getJobController($actionName)->$actionName(self::CANONICAL_URL, $job->getId())->getStatusCode());
    }
    
    public function testGetForPrivateJobOwnedByNonPublicUserByDifferentNonPublicUser() {        
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');
        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL, $user1->getEmail())); 
        
        $this->assertFalse($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $actionName = $this->getActionName();

        $this->getUserService()->setUser($user2);
        $this->assertEquals(403, $this->getJobController($actionName)->$actionName(self::CANONICAL_URL, $job->getId())->getStatusCode());
    }    
}


