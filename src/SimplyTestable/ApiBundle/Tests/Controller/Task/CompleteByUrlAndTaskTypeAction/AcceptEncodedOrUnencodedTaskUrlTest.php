<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteByUrlAndTaskTypeAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class AcceptEncodedOrUnencodedTaskUrlTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
    public function testWithCoreAppUrlUnencodedAndCompletionReportUrlEncoded() {        
        $this->createWorker();
        
        $canonicalUrl = 'http://example.com/foo bar/';
        $encodedCanonicalUrl = 'http://example.com/foo%20bar/';
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob($canonicalUrl, null, 'single url', array('HTML validation')));
        $this->getHttpClientService()->getMockPlugin()->clearQueue();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));

        $task = $job->getTasks()->first();        
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
        $this->createWorker();
        
        $canonicalUrl = 'http://example.com/foo bar/';
        $encodedCanonicalUrl = 'http://example.com/foo%20bar/';
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob($encodedCanonicalUrl, null, 'single url', array('HTML validation')));
        $this->getHttpClientService()->getMockPlugin()->clearQueue();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));

        $task = $job->getTasks()->first();        
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


