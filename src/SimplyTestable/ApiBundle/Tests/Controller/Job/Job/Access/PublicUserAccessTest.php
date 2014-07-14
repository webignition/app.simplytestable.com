<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\Access;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Job;

abstract class PublicUserAccessTest extends BaseControllerJsonTestCase {
    
    const CANONICAL_URL = 'http://www.example.com/';
    
    abstract protected function getActionName();

    /**
     * @var Job
     */
    private $job;

    public function setUp() {
        parent::setUp();
        $this->job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));
    }
    
    public function testGetForPublicJobOwnedByPublicUserByPublicUser() {
        $this->assertTrue($this->job->getIsPublic());
        $this->assertEquals($this->getUserService()->getPublicUser()->getId(), $this->job->getUser()->getId());
        
        $actionName = $this->getActionName();
        $this->assertEquals(200, $this->getJobController($actionName)->$actionName(self::CANONICAL_URL, $this->job->getId())->getStatusCode());
    }
    
    public function testGetForPublicJobOwnedByPublicUserByNonPublicUser() {
        $this->assertTrue($this->job->getIsPublic());
        $this->assertEquals($this->getUserService()->getPublicUser()->getId(), $this->job->getUser()->getId());
        
        $user = $this->createAndActivateUser('user@example.com', 'password');
        $actionName = $this->getActionName();

        $this->assertEquals(200, $this->getJobController($actionName, array(
            'user' => $user->getEmail()            
        ))->$actionName(self::CANONICAL_URL, $this->job->getId())->getStatusCode());
    } 
    

}


