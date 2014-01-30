<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\PrepareFromCrawl\NoDiscoveredUrls;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase {    
    
    const EXPECTED_TASK_TYPE_COUNT = 4;
    const CANONICAL_URL = 'http://example.com';
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    private $job;    
    
    public function setUp() {
        parent::setUp(); 
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404'
        )));
        
        $this->job = $this->getJobService()->getById($this->createAndPrepareJob(self::CANONICAL_URL, $this->getTestUser()->getEmail()));             
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($this->job);                
        $urlDiscoveryTask = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$urlDiscoveryTask->getUrl(), $urlDiscoveryTask->getType()->getName(), $urlDiscoveryTask->getParametersHash());        
    }   
    
    
    public function testStateIsQueued() {
        $this->assertEquals($this->getJobService()->getQueuedState(), $this->job->getState());
    }
    
    
    public function testHasStartTime() {
        $this->assertNotNull($this->job->getTimePeriod());
        $this->assertNotNull($this->job->getTimePeriod()->getStartDateTime());
    }
    
    
    public function testHasNotEndTime() {
        $this->assertNull($this->job->getTimePeriod()->getEndDateTime());
    } 
    
    
    public function testHasTasks() {
        $this->assertEquals(self::EXPECTED_TASK_TYPE_COUNT, $this->job->getTasks()->count());
    }
    
    
    public function testTaskUrls() {
        $expectedTaskUrls = array(
            'http://example.com/',
            'http://example.com/articles/',
            'http://example.com/articles/i-make-the-internet/'
        );          
        
        foreach ($this->job->getTasks() as $task) {
            $this->assertTrue(in_array($task->getUrl(), $expectedTaskUrls));
        }
    }
    
    
    public function testTaskStates() {        
        foreach ($this->job->getTasks() as $task) {
            $this->assertEquals($this->getTaskService()->getQueuedState(), $task->getState());
        }
    }  

}
