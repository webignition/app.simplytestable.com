<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\IsPublicAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class IsPublicActionTest extends BaseControllerJsonTestCase {
    
    const CANONICAL_URL = 'http://example.com';    
    
    public function testFalseForNonNumericJobId() { 
        $this->assertEquals(404, $this->getJobController('isPublicAction')->isPublicAction(self::CANONICAL_URL, 'foo')->getStatusCode());  
    }    
    
    public function testFalseForInvalidJobId() { 
        $this->assertEquals(404, $this->getJobController('isPublicAction')->isPublicAction(self::CANONICAL_URL, 0)->getStatusCode());  
    }      
    
    public function testTrueForJobOwnedByPublicUserAccessedByPublicUser() { 
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL);
        $this->assertEquals(200, $this->getJobController('isPublicAction')->isPublicAction(self::CANONICAL_URL, $jobId)->getStatusCode());  
    }
    
    public function testTrueForPublicJobOwnedByNonPublicUserAccessedByPublicUser() {
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getUserService()->setUser($user);
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $user->getEmail());
        
        $this->getJobController('setPublicAction')->setPublicAction(self::CANONICAL_URL, $jobId);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->assertEquals(200, $this->getJobController('isPublicAction')->isPublicAction(self::CANONICAL_URL, $jobId)->getStatusCode());
    }    
    
    public function testTrueForPublicJobOwnedByNonPublicUserAccessedByNonPublicUser() {
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getUserService()->setUser($user);
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $user->getEmail());
        
        $this->getJobController('setPublicAction')->setPublicAction(self::CANONICAL_URL, $jobId);
        
        $this->assertEquals(200, $this->getJobController('isPublicAction')->isPublicAction(self::CANONICAL_URL, $jobId)->getStatusCode());
    }
    
    public function testTrueForPublicJobOwnedByNonPublicUserAccessedByDifferentNonPublicUser() {
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');

        $this->getUserService()->setUser($user1);
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $user1->getEmail());
        
        $this->getJobController('setPublicAction')->setPublicAction(self::CANONICAL_URL, $jobId);

        $this->getUserService()->setUser($user2);
        $this->assertEquals(200, $this->getJobController('isPublicAction')->isPublicAction(self::CANONICAL_URL, $jobId)->getStatusCode());
    }
    
    public function testFalseForPrivateJobOwnedByNonPublicUserAccessedByPublicUser() {
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getUserService()->setUser($user);
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $user->getEmail());

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->assertEquals(404, $this->getJobController('isPublicAction')->isPublicAction(self::CANONICAL_URL, $jobId)->getStatusCode());
    }    
    
    public function testFalseForPrivateJobOwnedByNonPublicUserAccessedByNonPublicUser() {
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $this->getUserService()->setUser($user);
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $user->getEmail());
        
        $this->assertEquals(404, $this->getJobController('isPublicAction')->isPublicAction(self::CANONICAL_URL, $jobId)->getStatusCode());
    }
    
    public function testFalseForPrivateJobOwnedByNonPublicUserAccessedByDifferentNonPublicUser() {
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');

        $this->getUserService()->setUser($user1);
        $jobId = $this->createJobAndGetId(self::CANONICAL_URL, $user1->getEmail());

        $this->getUserService()->setUser($user2);
        $this->assertEquals(404, $this->getJobController('isPublicAction')->isPublicAction(self::CANONICAL_URL, $jobId)->getStatusCode());
    }        
}


