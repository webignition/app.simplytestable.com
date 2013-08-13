<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\AssignmentSelectionCommand;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class UrlDiscoverySelectionTest extends BaseSimplyTestableTestCase {    
    const WORKER_TASK_ASSIGNMENT_FACTOR = 2;   
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
    public function testUrlDiscoveryTaskIsSelectedForAssigment() {
        $this->createWorkers(1);
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->create($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign:select'));        
        $this->assertEquals('task-queued-for-assignment', $crawlJobContainer->getCrawlJob()->getTasks()->first()->getState()->getName());
    }
    
}
