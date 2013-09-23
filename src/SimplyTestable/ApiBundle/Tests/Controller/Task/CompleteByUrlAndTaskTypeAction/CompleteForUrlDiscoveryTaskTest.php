<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteByUrlAndTaskTypeAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CompleteForUrlDiscoveryTaskTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }   


    public function testCompleteForTaskDiscoveringUrlsDoesNotMarkJobAsComplete() {
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
        
        $this->assertEquals('task-in-progress', $task->getState()->getName());
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/","http:\/\/example.com\/two\/","http:\/\/example.com\/three\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());             
        
        $this->assertTrue($crawlJobContainer->getCrawlJob()->getState()->equals($this->getJobService()->getInProgressState()));
    }
    
    public function testCompleteForTaskDiscoveringNoUrlsMarksJobAsComplete() {
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
    
    
    public function testParentJobIsRestartedOnceCrawlJobCompletesForSingleUrl() {
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
        
        $this->assertEquals('task-in-progress', $task->getState()->getName());
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $expectedTaskCount = count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)) * $crawlJobContainer->getParentJob()->getRequestedTaskTypes()->count();
        
        $this->assertTrue($this->getJobService()->isQueued($crawlJobContainer->getParentJob()));
        $this->assertEquals($expectedTaskCount, $crawlJobContainer->getParentJob()->getTasks()->count());    
    }
    
    public function testParentJobIsRestartedOnceCrawlJobCompletesForMultipleUrls() {
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
        
        $this->assertEquals('task-in-progress', $task->getState()->getName());
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($canonicalUrl, 10)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
       
        
        $expectedTaskCount = count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)) * $crawlJobContainer->getParentJob()->getRequestedTaskTypes()->count();
        
        $this->assertTrue($this->getJobService()->isQueued($crawlJobContainer->getParentJob()));
        $this->assertEquals($expectedTaskCount, $crawlJobContainer->getParentJob()->getTasks()->count());    
    }
    
    public function testParentJobIsRestartedOnceCrawlJobReachesAccountPlanUrlConstraint() {
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
        
        $this->assertEquals('task-in-progress', $task->getState()->getName());
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($canonicalUrl, 7)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
        $task = $this->getTaskService()->getById($taskIds[1]);
        
        $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        ));         
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($canonicalUrl, 7, 7)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
       ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());               
        
        $expectedTaskCount = count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)) * $crawlJobContainer->getParentJob()->getRequestedTaskTypes()->count();
        
        $this->assertTrue($this->getJobService()->isCompleted($crawlJobContainer->getCrawlJob()));
        $this->assertTrue($this->getJobService()->isQueued($crawlJobContainer->getParentJob()));
        $this->assertEquals($expectedTaskCount, $crawlJobContainer->getParentJob()->getTasks()->count());    
    }
    
    public function testCrawlJobAmmendmentsArePassedToParentJobOnRestart() {
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
        
        $this->assertEquals('task-in-progress', $task->getState()->getName());
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($canonicalUrl, 7)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
        $task = $this->getTaskService()->getById($taskIds[1]);
        
        $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        ));         
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($canonicalUrl, 7, 7)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());                       
        
        $this->assertEquals(1, $crawlJobContainer->getCrawlJob()->getAmmendments()->count());
        $this->assertEquals(1, $crawlJobContainer->getParentJob()->getAmmendments()->count());
    }
}


