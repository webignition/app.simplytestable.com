<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CrawlStatusTest extends BaseControllerJsonTestCase {
    
    public function testWithQueuedCrawlJob() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
        
        $jobObject = json_decode($this->getJobController('statusAction', array(
            'user' => $this->getTestUser()->getEmail()
        ))->statusAction((string)$job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals('queued', $jobObject->crawl->state);
        $this->assertEquals(10, $jobObject->crawl->limit);
    } 
    
    public function testWithInProgressCrawlJob() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));

        $this->createWorker();
       
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);    
        
        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        
        $this->assertEquals(0, $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        )));
        
        $this->assertEquals($this->getTaskService()->getInProgressState(), $task->getState());
        
        $urlCountToDiscover = (int)round($this->getUserAccountPlanService()->getForUser($task->getJob()->getUser())->getPlan()->getConstraintNamed('urls_per_job')->getLimit() / 2);        
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($job->getWebsite()->getCanonicalUrl(), $urlCountToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $jobObject = json_decode($this->getJobController('statusAction', array(
            'user' => $job->getUser()->getEmail()
        ))->statusAction((string)$job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals('in-progress', $jobObject->crawl->state);
        $this->assertEquals(1, $jobObject->crawl->processed_url_count);
        $this->assertEquals(6, $jobObject->crawl->discovered_url_count);
        $this->assertEquals(10, $jobObject->crawl->limit);    
    }
    
    public function testCrawlJobIdIsExposed() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
                
        $jobObject = json_decode($this->getJobController('statusAction', array(
            'user' => $job->getUser()->getEmail()
        ))->statusAction((string)$job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals('queued', $jobObject->crawl->state);
        $this->assertEquals(10, $jobObject->crawl->limit);
        $this->assertNotNull($jobObject->crawl->id);        
    }
    
    public function testGetForPublicJobOwnedByNonPublicUserByPublicUser() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
        
        $this->getJobController('setPublicAction', array(
            'user' => $this->getTestUser()->getEmail()
        ))->setPublicAction($job->getWebsite()->getCanonicalUrl(), $job->getId()); 
        
        $jobObject = json_decode($this->fetchJobResponse($job)->getContent());
        
        $this->assertTrue($job->getIsPublic());
        $this->assertTrue(isset($jobObject->crawl));       
    }    
    
    public function testGetForPublicJobOwnedByNonPublicUserByNonPublicUser() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
        
        $this->getJobController('setPublicAction', array(
            'user' => $this->getTestUser()->getEmail()
        ))->setPublicAction($job->getWebsite()->getCanonicalUrl(), $job->getId()); 
        
        $jobObject = json_decode($this->fetchJobResponse($job, array(
            'user' => $this->getTestUser()->getEmail()
        ))->getContent());
        
        $this->assertTrue($job->getIsPublic());
        $this->assertTrue(isset($jobObject->crawl));       
    }
    
    public function testGetForPublicJobOwnedByNonPublicUserByDifferentNonPublicUser() {
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');        
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(
            self::DEFAULT_CANONICAL_URL,
            $user1->getEmail()
        ));
        
        $this->getJobController('setPublicAction', array(
            'user' => $user1->getEmail()
        ))->setPublicAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
        
        $jobObject = json_decode($this->fetchJobResponse($job, array(
            'user' => $user2->getEmail()
        ))->getContent());        
        
        $this->assertTrue($job->getIsPublic());        
        $this->assertTrue(isset($jobObject->crawl));         
    }    
    
    public function testGetForPrivateJobOwnedByNonPublicUserByPublicUser() {                
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(
            self::DEFAULT_CANONICAL_URL,
            $this->getTestUser()->getEmail()
        )); 
        
        $this->assertEquals(403, $this->fetchJobResponse($job)->getStatusCode());
    }    
    
    public function testGetForPrivateJobOwnedByNonPublicUserByNonPublicUser() {      
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(
            self::DEFAULT_CANONICAL_URL,
            $this->getTestUser()->getEmail()
        ));
        
        $jobObject = json_decode($this->fetchJobResponse($job, array(
            'user' => $job->getUser()->getEmail()
        ))->getContent());
        
        $this->assertTrue(isset($jobObject->crawl));            
    }
    
    public function testGetForPrivateJobOwnedByNonPublicUserByDifferentNonPublicUser() {        
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');        
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(
            self::DEFAULT_CANONICAL_URL,
            $user1->getEmail()
        ));        
        
        $this->assertEquals(403, $this->fetchJobResponse($job, array(
            'user' => $user2->getEmail()
        ))->getStatusCode());              
    }   
    
    
    public function testGetJobOwnerCrawlLimitForPublicJobOwnedByPrivateUser() {
        $user = $this->getTestUser();
        $this->getUserService()->setUser($user);
        
        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction($user->getEmail(), 'agency');
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);        
        $accountPlanUrlLimit = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit();

        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(
            self::DEFAULT_CANONICAL_URL,
            $user->getEmail()
        ));
        
        $this->getJobController('setPublicAction', array(
            'user' => $user->getEmail()
        ))->setPublicAction($job->getWebsite()->getCanonicalUrl(), $job->getId());        
        
        $jobObject = json_decode($this->fetchJobResponse($job)->getContent()); 
        
        $this->assertTrue($job->getIsPublic());
        $this->assertEquals($accountPlanUrlLimit, $jobObject->crawl->limit);        
    }
    
}


