<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\CrawlJob\StartAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class StartActionTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }      
   
    public function testStartForInvalidTestReturns403() {
        $canonicalUrl = 'http://example.com';
        $jobId = 1;
        
        $response = $this->getCrawlJobController('startAction')->startAction($canonicalUrl, $jobId);
        $this->assertEquals(403, $response->getStatusCode());
    }
    
    public function testStartForInvalidTestStateReturns400() {
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));

        $response = $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());
        $this->assertEquals(400, $response->getStatusCode());
    }    
    
    public function testStartWithNoExistingCrawlJob() {
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);

        $response = $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());
        $this->assertEquals(200, $response->getStatusCode());
    }    
    
    public function testStartWithExistingCrawlJob() {
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);
        
        $this->getCrawlJobContainerService()->getForJob($job);

        $response = $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());
        $this->assertEquals(200, $response->getStatusCode());
    }  
    
    public function testStartAlsoPreparesCrawlJob() {
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);

        $response = $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());
        $this->assertEquals(200, $response->getStatusCode());        
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);

        $this->assertEquals(1, $crawlJobContainer->getCrawlJob()->getTasks()->count());      
        $this->assertEquals('URL discovery', $crawlJobContainer->getCrawlJob()->getTasks()->first()->getType()->getName());
    }
    
    public function testRestartCrawlJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->createWorker();
        
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);

        $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId());     
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        ));  
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($canonicalUrl, 3)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        $completedTaskId = $task->getId();

        $this->getJobController('cancelAction')->cancelAction((string)$crawlJobContainer->getCrawlJob()->getWebsite(), $crawlJobContainer->getCrawlJob()->getId());
        
        $this->assertFalse($this->getJobService()->hasIncompleteTasks($crawlJobContainer->getCrawlJob()));
        
        $this->getCrawlJobController('startAction')->startAction((string)$job->getWebsite(), $job->getId()); 
        $this->assertTrue($this->getJobService()->hasIncompleteTasks($crawlJobContainer->getCrawlJob()));
    
        foreach ($crawlJobContainer->getCrawlJob()->getTasks() as $task) {
            if ($task->getId() != $completedTaskId) {
                $this->assertTrue($task->getState()->equals($this->getTaskService()->getQueuedState()));
            }
        }   
    }
    
}


