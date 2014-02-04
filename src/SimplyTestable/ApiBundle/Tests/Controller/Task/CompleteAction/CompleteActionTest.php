<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CompleteActionTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }       
   
    public function testCompleteAction() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));    
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');       
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $job->getTasks()->first()->getId()
        ));        
       
        $this->assertEquals($this->getJobService()->getInProgressState(), $job->getState());
        
        $response = $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction('http://hydrogen.worker.simplytestable.com', 1);
        
        $this->assertEquals(200, $response->getStatusCode());
    }    
    
    public function testCompleteActionInMaintenanceReadOnlyModeReturns503() {                
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        
        $response = $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction('hydrogen.worker.simplytestable.com', 1);
        
        $this->assertEquals(503, $response->getStatusCode());       
    }
    
    public function testCompleteActionForNonExistentTaskReturns410() { 
        $this->createWorker('hydrogen.worker.simplytestable.com');
        
        $response = $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction('hydrogen.worker.simplytestable.com', 1);
        
        $this->assertEquals(410, $response->getStatusCode());
    }

    
}


