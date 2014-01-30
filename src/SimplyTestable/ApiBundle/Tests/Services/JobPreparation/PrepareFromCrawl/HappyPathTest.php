<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\PrepareFromCrawl;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class HappyPathTest extends BaseSimplyTestableTestCase {    
    
    const EXPECTED_TASK_TYPE_COUNT = 4;
    const CANONICAL_URL = 'http://example.com';
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\CrawlJobContainer
     */
    private $crawlJobContainer;    
    
    public function setUp() {
        parent::setUp(); 
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404'
        )));
        
        $job = $this->getJobService()->getById($this->createAndPrepareJob(self::CANONICAL_URL, $this->getTestUser()->getEmail()));             
        $this->crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);                
        $urlDiscoveryTask = $this->crawlJobContainer->getCrawlJob()->getTasks()->first();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::CANONICAL_URL, 1)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$urlDiscoveryTask->getUrl(), $urlDiscoveryTask->getType()->getName(), $urlDiscoveryTask->getParametersHash());        
        
        $this->getJobPreparationService()->prepareFromCrawl($this->crawlJobContainer);
    }   
    
    
    public function testStateIsQueued() {
        $this->assertEquals($this->getJobService()->getQueuedState(), $this->getJob()->getState());
    }
    
    
    public function testHasStartTime() {
        $this->assertNotNull($this->getJob()->getTimePeriod());
        $this->assertNotNull($this->getJob()->getTimePeriod()->getStartDateTime());
    }
    
    
    public function testHasNotEndTime() {
        $this->assertNull($this->getJob()->getTimePeriod()->getEndDateTime());
    } 
    
    
    public function testHasTasks() {        
        $this->assertEquals($this->getExpectedTaskCount(), $this->getJob()->getTasks()->count());
    }
    
    
    public function testTaskStates() {        
        foreach ($this->getJob()->getTasks() as $task) {
            $this->assertEquals($this->getTaskService()->getQueuedState(), $task->getState());
        }
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    private function getJob() {
        return $this->crawlJobContainer->getParentJob();
    }
    

    /**
     * 
     * @return int
     */
    private function getExpectedTaskCount() {
        return self::EXPECTED_TASK_TYPE_COUNT * count($this->getCrawlJobContainerService()->getDiscoveredUrls($this->crawlJobContainer));
    }

}
