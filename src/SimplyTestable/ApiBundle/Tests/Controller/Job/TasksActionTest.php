<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class TasksActionTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    
    
    public function testNoOutputForIncompleteTasksWithPartialOutput() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, null, 'full site', array('Link integrity')));

        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode(array(
                array(
                    'context' => '<a href="http://example.com/one">Example One</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/one'
                ),
                array(
                    'context' => '<a href="http://example.com/two">Example Two</a>',
                    'state' => 200,
                    'type' => 'http',
                    'url' => 'http://example.com/two'
                ),
                array(
                    'context' => '<a href="http://example.com/three">Example Three</a>',
                    'state' => 204,
                    'type' => 'http',
                    'url' => 'http://example.com/three'
                )            
            )),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 1,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
        
        $this->runConsole('simplytestable:task:assign', array(
            $tasks[1]->getId() =>  true
        )); 
        
        $tasksResponseObject = json_decode($this->getJobController('tasksAction')->tasksAction($canonicalUrl, $job->getId())->getContent());
        
        foreach ($tasksResponseObject as $taskResponse) {            
            if ($taskResponse->id == $tasks[0]->getId()) {
                $this->assertTrue(isset($taskResponse->output));
            } else {
                $this->assertFalse(isset($taskResponse->output));
            }
        }   
    }
    
    
    public function testGetForPublicJobOwnedByPublicUserByPublicUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $this->assertTrue($job->getIsPublic());
        $this->assertEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $tasksResponse = $this->getJobController('tasksAction')->tasksAction($canonicalUrl, $job->getId());
        $this->assertEquals(200, $tasksResponse->getStatusCode());      
    }
    
    public function testGetForPublicJobOwnedByPublicUserByNonPublicUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $this->assertTrue($job->getIsPublic());
        $this->assertEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());        
        
        $tasksResponse = $this->getJobController('tasksAction', array(
            'user' => $user->getEmail()
        ))->tasksAction($canonicalUrl, $job->getId());
        $this->assertEquals(200, $tasksResponse->getStatusCode());          
    } 
    
    public function testGetForPublicJobOwnedByNonPublicUserByNonPublicUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, $user->getEmail()));
        $job->setIsPublic(true);
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertTrue($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());         
        
        $tasksResponse = $this->getJobController('tasksAction', array(
            'user' => $user->getEmail()
        ))->tasksAction($canonicalUrl, $job->getId());
        $this->assertEquals(200, $tasksResponse->getStatusCode());         
    }
    
    public function testGetForPublicJobOwnedByNonPublicUserByDifferenNonPublicUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));
        
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, $user1->getEmail()));
        $job->setIsPublic(true);
        $this->getJobService()->persistAndFlush($job);        
        
        $this->assertTrue($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $tasksResponse = $this->getJobController('tasksAction', array(
            'user' => $user2->getEmail()
        ))->tasksAction($canonicalUrl, $job->getId());
        $this->assertEquals(200, $tasksResponse->getStatusCode());          
    }    
    
    public function testGetForPrivateJobOwnedByNonPublicUserByPublicUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, $user->getEmail()));
        
        $this->assertFalse($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $tasksResponse = $this->getJobController('tasksAction')->tasksAction($canonicalUrl, $job->getId());
        $this->assertEquals(403, $tasksResponse->getStatusCode());            
    }    

    
    public function testGetForPrivateJobOwnedByNonPublicUserByNonPublicUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, $user->getEmail()));
        
        $this->assertFalse($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $tasksResponse = $this->getJobController('tasksAction', array(
            'user' => $user->getEmail()
        ))->tasksAction($canonicalUrl, $job->getId());
        $this->assertEquals(200, $tasksResponse->getStatusCode());            
    }
    
    public function testGetForPrivateJobOwnedByNonPublicUserByDifferentNonPublicUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));
        
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, $user1->getEmail()));    
        
        $this->assertFalse($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $tasksResponse = $this->getJobController('tasksAction', array(
            'user' => $user2->getEmail()
        ))->tasksAction($canonicalUrl, $job->getId());
        $this->assertEquals(403, $tasksResponse->getStatusCode());          
    }     
    
}


