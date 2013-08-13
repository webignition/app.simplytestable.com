<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteByUrlAndTaskTypeAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CompleteForUrlDiscoveryTaskTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }   


//    public function testCompleteForTaskDiscoveringUrlsDoesNotMarkJobAsComplete() {
//        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
//        $this->createWorker('http://hydrogen.worker.simplytestable.com');
//        
//        $canonicalUrl = 'http://example.com/';
//        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
//        
//        $crawlJobContainer = $this->getCrawlJobContainerService()->create($job);        
//        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
//        
//        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
//        $task = $this->getTaskService()->getById($taskIds[0]);
//        
//        $this->runConsole('simplytestable:task:assign', array(
//            $task->getId() =>  true
//        ));
//        
//        $this->assertEquals('task-in-progress', $task->getState()->getName());
//        
//        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
//            'end_date_time' => '2012-03-08 17:03:00',
//            'output' => '["http:\/\/example.com\/one\/","http:\/\/example.com\/two\/","http:\/\/example.com\/three\/"]',
//            'contentType' => 'application/json',
//            'state' => 'completed',
//            'errorCount' => 0,
//            'warningCount' => 0
//        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());             
//        
//        $this->assertTrue($crawlJobContainer->getCrawlJob()->getState()->equals($this->getJobService()->getInProgressState()));
//    }
    
    public function testCompleteForTaskDiscoveringNoUrlsMarksJobAsComplete() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->create($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        ));
        
        $this->assertEquals('task-in-progress', $task->getState()->getName());
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());             
        
        $this->assertTrue($crawlJobContainer->getCrawlJob()->getState()->equals($this->getJobService()->getCompletedState()));
    }    
}


