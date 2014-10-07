<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\PrepareFromCrawl;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class HappyPathTest extends BaseSimplyTestableTestCase {    
    
    const EXPECTED_TASK_TYPE_COUNT = 4;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\CrawlJobContainer
     */
    private $crawlJobContainer;    
    
    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
        
        $this->crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);                
        $urlDiscoveryTask = $this->crawlJobContainer->getCrawlJob()->getTasks()->first();
        
        $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, 1)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction((string)$urlDiscoveryTask->getUrl(), $urlDiscoveryTask->getType()->getName(), $urlDiscoveryTask->getParametersHash());
        
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
