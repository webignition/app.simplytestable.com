<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer\ProcessTaskResults;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ProcessTaskResultsTest extends BaseSimplyTestableTestCase {
    
    public function testWithUrlsNotYetProcessed() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        
        $this->createWorker();
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $this->assertEquals($this->getTaskService()->getInProgressState(), $task->getState());
        
        $response = $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/","http:\/\/example.com\/two\/","http:\/\/example.com\/three\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $this->assertTrue($this->getCrawlJobContainerService()->processTaskResults($task));        
        $this->assertEquals(4, $crawlJobContainer->getCrawlJob()->getTasks()->count());      
    }
    
    public function testWithAllUrlsAlreadyProcessed() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        
        $this->createWorker();
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $this->getCrawlJobContainerService()->processTaskResults($task);
        $this->assertEquals(2, $crawlJobContainer->getCrawlJob()->getTasks()->count()); 
        
        $task = $crawlJobContainer->getCrawlJob()->getTasks()->get(1);
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $this->getCrawlJobContainerService()->processTaskResults($task);
     
        $this->assertEquals(2, $crawlJobContainer->getCrawlJob()->getTasks()->count());      
        
        foreach ($crawlJobContainer->getCrawlJob()->getTasks() as $task) {
            $this->assertEquals('task-completed', $task->getState()->getName());   
        }
    }    

}
