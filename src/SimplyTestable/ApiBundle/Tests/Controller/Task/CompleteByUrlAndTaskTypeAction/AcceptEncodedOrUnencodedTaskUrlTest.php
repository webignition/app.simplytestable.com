<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteByUrlAndTaskTypeAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class AcceptEncodedOrUnencodedTaskUrlTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
    public function testWithCoreAppUrlUnencodedAndCompletionReportUrlEncoded() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/foo bar/';
        $encodedCanonicalUrl = 'http://example.com/foo%20bar/';
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl, null, 'single url', array('HTML validation'))->getTargetUrl());

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $response = $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction($encodedCanonicalUrl, $task->getType()->getName(), $task->getParametersHash());
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('task-completed', $task->getState()->getName());
    }    

    public function testWithCoreAppUrlEncodedAndCompletionReportUrlUnencoded() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/foo bar/';
        $encodedCanonicalUrl = 'http://example.com/foo%20bar/';
        $job_id = $this->getJobIdFromUrl($this->createJob($encodedCanonicalUrl, null, 'single url', array('HTML validation'))->getTargetUrl());

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));        
        
        $response = $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction($canonicalUrl, $task->getType()->getName(), $task->getParametersHash());
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('task-completed', $task->getState()->getName());
    }
    
}


