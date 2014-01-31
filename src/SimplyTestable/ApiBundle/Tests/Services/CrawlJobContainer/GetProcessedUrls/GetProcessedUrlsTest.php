<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer\GetProcessedUrls;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class GetProcessedUrlsTest extends BaseSimplyTestableTestCase {
    
    public function testGetProcessedUrls() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
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
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
        $task = $this->getTaskService()->getById($taskIds[1]);
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
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
        
        $this->assertEquals(array(
            'http://example.com/',
            'http://example.com/one/'
        ), $this->getCrawlJobContainerService()->getProcessedUrls($crawlJobContainer));
    }    

}
