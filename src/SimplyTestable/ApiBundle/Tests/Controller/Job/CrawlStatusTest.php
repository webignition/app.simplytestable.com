<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CrawlStatusTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }
    
    public function testWithQueuedCrawlJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());
        
        $jobObject = json_decode($this->getJobController('statusAction')->statusAction((string)$job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals('queued', $jobObject->crawl->state);
        $this->assertEquals(10, $jobObject->crawl->limit);
    } 
    
    public function testWithInProgressCrawlJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());      
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        ));
        
        $urlCountToDiscover = (int)round($this->getUserAccountPlanService()->getForUser($task->getJob()->getUser())->getPlan()->getConstraintNamed('urls_per_job')->getLimit() / 2);
        
        $this->assertEquals('task-in-progress', $task->getState()->getName());
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($canonicalUrl, $urlCountToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $jobObject = json_decode($this->getJobController('statusAction')->statusAction((string)$job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals('in-progress', $jobObject->crawl->state);
        $this->assertEquals(1, $jobObject->crawl->processed_url_count);
        $this->assertEquals(6, $jobObject->crawl->discovered_url_count);
        $this->assertEquals(10, $jobObject->crawl->limit);
    }
    
    public function testCrawlJobIdIsExposed() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());
        
        $jobObject = json_decode($this->getJobController('statusAction')->statusAction((string)$job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals('queued', $jobObject->crawl->state);
        $this->assertEquals(10, $jobObject->crawl->limit);
        $this->assertNotNull($jobObject->crawl->id);        
    }     
    
}


