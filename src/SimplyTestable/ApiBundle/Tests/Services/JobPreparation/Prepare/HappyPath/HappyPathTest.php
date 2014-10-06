<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\HappyPath;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class HappyPathTest extends BaseSimplyTestableTestCase {    

    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    private $job;
    
    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->job = $this->getJobService()->getById($this->createAndResolveDefaultJob());        
        $this->getHttpClientService()->queueFixtures($this->buildHttpFixtureSet($this->getFixtureMessages()));      
        $this->getJobPreparationService()->prepare($this->job);
    }  
    
    abstract protected function getFixtureMessages(); 
    
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
            'http://example.com/0/',
            'http://example.com/0/',
            'http://example.com/0/',
            'http://example.com/0/',
            'http://example.com/1/',
            'http://example.com/1/',
            'http://example.com/1/',
            'http://example.com/1/',
            'http://example.com/2/',
            'http://example.com/2/',
            'http://example.com/2/',
            'http://example.com/2/',
        );
        
        foreach ($this->job->getTasks() as $index => $task) {                        
            $this->assertEquals($expectedTaskUrls[$index], $task->getUrl(), 'Task at index ' . $index. ' does not have URL "'.$expectedTaskUrls[$index].'"');
        }
    }
    
    public function testCurlOptionsAreSetOnAllRequests() {
        $this->assertSystemCurlOptionsAreSetOnAllRequests();
    }    
    
    
    public function testTaskStates() {        
        foreach ($this->job->getTasks() as $task) {
            $this->assertEquals($this->getTaskService()->getQueuedState(), $task->getState());
        }
    }

}
