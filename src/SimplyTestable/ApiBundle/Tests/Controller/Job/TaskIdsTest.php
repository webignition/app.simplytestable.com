<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class TaskIdsTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    
    
    public function testTaskIdsAction() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $job = $this->prepareJob($canonicalUrl, $job_id);
        
        $response = $this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id);
        $taskIds = json_decode($response->getContent());
        
        $expectedTaskIdCount = $job->url_count * count($job->task_types);
        
        $this->assertEquals($expectedTaskIdCount, count($taskIds));
        
        foreach ($taskIds as $taskId) {
            $this->assertInternalType('integer', $taskId);
            $this->assertGreaterThan(0, $taskId);
        }    
    }
    
    
    public function testGetForPublicJobOwnedByPublicUserByPublicUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $this->assertTrue($job->getIsPublic());
        $this->assertEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $tasksResponse = $this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job->getId());
        $this->assertEquals(200, $tasksResponse->getStatusCode());      
    }
    
    public function testGetForPublicJobOwnedByPublicUserByNonPublicUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $this->assertTrue($job->getIsPublic());
        $this->assertEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());        
        
        $tasksResponse = $this->getJobController('taskIdsAction', array(
            'user' => $user->getEmail()
        ))->taskIdsAction($canonicalUrl, $job->getId());
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
        
        $tasksResponse = $this->getJobController('taskIdsAction', array(
            'user' => $user->getEmail()
        ))->taskIdsAction($canonicalUrl, $job->getId());
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
        
        $tasksResponse = $this->getJobController('taskIdsAction', array(
            'user' => $user2->getEmail()
        ))->taskIdsAction($canonicalUrl, $job->getId());
        $this->assertEquals(200, $tasksResponse->getStatusCode());          
    }    
    
    public function testGetForPrivateJobOwnedByNonPublicUserByPublicUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, $user->getEmail()));
        
        $this->assertFalse($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $tasksResponse = $this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job->getId());
        $this->assertEquals(403, $tasksResponse->getStatusCode());            
    }    

    
    public function testGetForPrivateJobOwnedByNonPublicUserByNonPublicUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, $user->getEmail()));
        
        $this->assertFalse($job->getIsPublic());
        $this->assertNotEquals($this->getUserService()->getPublicUser()->getId(), $job->getUser()->getId());
        
        $tasksResponse = $this->getJobController('taskIdsAction', array(
            'user' => $user->getEmail()
        ))->taskIdsAction($canonicalUrl, $job->getId());
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
        
        $tasksResponse = $this->getJobController('taskIdsAction', array(
            'user' => $user2->getEmail()
        ))->taskIdsAction($canonicalUrl, $job->getId());
        $this->assertEquals(403, $tasksResponse->getStatusCode());          
    }      
    
}


