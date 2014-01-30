<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class HappyPathTest extends BaseSimplyTestableTestCase {    
    
    const CANONICAL_URL = 'http://example.com';    

    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    private $job;
    
    public function setUp() {
        parent::setUp();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(). '/HttpResponses'));
        $this->job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));
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
