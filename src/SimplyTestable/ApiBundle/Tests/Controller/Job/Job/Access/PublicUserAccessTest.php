<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\Access;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

abstract class PublicUserAccessTest extends BaseControllerJsonTestCase {
    
    const CANONICAL_URL = 'http://www.example.com/';
    
    abstract protected function getActionName();
    
    public function testGetForPublicJobOwnedByPublicUserByPublicUser() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));
        
        $this->assertTrue($job->getIsPublic());
        $this->assertEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $actionName = $this->getActionName();
        $this->assertEquals(200, $this->getJobController($actionName)->$actionName(self::CANONICAL_URL, $job->getId())->getStatusCode());      
    }
    
    public function testGetForPublicJobOwnedByPublicUserByNonPublicUser() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));
        $this->assertTrue($job->getIsPublic());
        $this->assertEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId()); 
        
        $user = $this->createAndActivateUser('user@example.com', 'password');
        $actionName = $this->getActionName();

        $this->assertEquals(200, $this->getJobController($actionName, array(
            'user' => $user->getEmail()            
        ))->$actionName(self::CANONICAL_URL, $job->getId())->getStatusCode());              
    } 
    

}


