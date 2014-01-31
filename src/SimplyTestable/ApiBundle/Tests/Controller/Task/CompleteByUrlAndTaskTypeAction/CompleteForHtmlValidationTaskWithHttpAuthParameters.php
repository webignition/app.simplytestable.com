<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteByUrlAndTaskTypeAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CompleteForHtmlValidationTaskWithHttpAuthParameters extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }   


    public function testReportCompletionWithHttpAuthParameters() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, null, null, array(
            'html validation'
        ), null, array(
            'http-auth' => '1',
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'
        )));
        
        $task = $job->getTasks()->first();
        $this->assertNull($task->getOutput());
     
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $this->assertEquals('task-in-progress', $task->getState()->getName());
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '{"messages":[]}',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());             
        
        $this->assertEquals('task-completed', $task->getState()->getName());
        $this->assertNotNull($task->getOutput());
    }

}


