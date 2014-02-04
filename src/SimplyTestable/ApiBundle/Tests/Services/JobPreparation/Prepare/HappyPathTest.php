<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class HappyPathTest extends BaseSimplyTestableTestCase {    

    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    private $job;
    
    public function setUp() {
        parent::setUp();

        $this->job = $this->getJobService()->getById($this->createAndResolveDefaultJob());        
        $this->queuePrepareHttpFixturesForJob($this->job->getWebsite()->getCanonicalUrl());        
        $this->getJobPreparationService()->prepare($this->job);
    }
    
    public function testPreparationThrowsNoExceptions() {}
    
    
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
        $this->assertGreaterThan(0, $this->job->getTasks()->count());
    }
    
    
    public function testTaskUrls() {        
        foreach ($this->job->getTasks() as $task) {            
            $this->assertTrue(in_array($task->getUrl(), array(
                'http://example.com/0/',
                'http://example.com/1/',
                'http://example.com/2/'
            )));
        }
    }
    
    
    public function testTaskStates() {        
        foreach ($this->job->getTasks() as $task) {
            $this->assertEquals($this->getTaskService()->getQueuedState(), $task->getState());
        }
    }

}
