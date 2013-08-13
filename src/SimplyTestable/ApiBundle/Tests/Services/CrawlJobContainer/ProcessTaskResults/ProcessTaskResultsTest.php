<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer\ProcessTaskResults;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class ProcessTaskResultsTest extends BaseSimplyTestableTestCase {
    
    public function testWithUrlsNotYetProcessed() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->create($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        )));
        
        $this->assertEquals('task-in-progress', $task->getState()->getName());
        
        $response = $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/","http:\/\/example.com\/two\/","http:\/\/example.com\/three\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());             
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $this->assertTrue($this->getCrawlJobContainerService()->processTaskResults($task));        
        $this->assertEquals(4, $crawlJobContainer->getCrawlJob()->getTasks()->count());      
    }
    
    public function testWithAllUrlsAlreadyProcessed() {
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
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());             
        
        $this->getCrawlJobContainerService()->processTaskResults($task);
        $this->assertEquals(2, $crawlJobContainer->getCrawlJob()->getTasks()->count()); 
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
        $task = $this->getTaskService()->getById($taskIds[1]);
        
        $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        ));
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());        
        
        $this->getCrawlJobContainerService()->processTaskResults($task);
     
        $this->assertEquals(2, $crawlJobContainer->getCrawlJob()->getTasks()->count());      
        
        foreach ($crawlJobContainer->getCrawlJob()->getTasks() as $task) {
            $this->assertEquals('task-completed', $task->getState()->getName());   
        }
    }    

}
