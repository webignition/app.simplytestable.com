<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class AssignSelectedCommandTest extends BaseSimplyTestableTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }    
    
    public function testAssignValidTaskReturnsStatusCode0() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';           
        $job_id = $this->createAndPrepareJob($canonicalUrl);
        
        $this->assertInternalType('integer', $job_id);
        $this->assertGreaterThan(0, $job_id);
        
        $this->createWorker('hydrogen.worker.simplytestable.com');   

        $taskIds = $this->getTaskIds($canonicalUrl, $job_id);
        $task = $this->getTaskService()->getById($taskIds[0]);
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();        
        
        $preAssignJobResponse = $this->fetchJob($canonicalUrl, $job_id);        
        $preAssignJobObject = json_decode($preAssignJobResponse->getContent());
        
        $this->assertEquals(200, $preAssignJobResponse->getStatusCode());
        $this->assertEquals(1, $preAssignJobObject->task_count_by_state->{'queued-for-assignment'});
        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign-selected'));
        
        $postAssignJobResponse = $this->fetchJob($canonicalUrl, $job_id);        
        $postAssignJobObject = json_decode($postAssignJobResponse->getContent()); 
        
        $this->assertEquals(0, $postAssignJobObject->task_count_by_state->{'queued-for-assignment'});
        $this->assertEquals(1, $postAssignJobObject->task_count_by_state->{'in-progress'});      
    }

    
    public function testAssignTaskWhenNoWorkersReturnsStatusCode1() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';        
        $job_id = $this->createAndPrepareJob('http://example.com/');
        
        $taskIds = $this->getTaskIds($canonicalUrl, $job_id);
        $task = $this->getTaskService()->getById($taskIds[0]);
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertEquals(1, $this->runConsole('simplytestable:task:assign-selected'));       
    }

    
    public function testAssignTaskWhenNoWorkersAreAvailableReturnsStatusCode2() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';           
        $job_id = $this->createAndPrepareJob($canonicalUrl);
        
        $this->assertInternalType('integer', $job_id);
        $this->assertGreaterThan(0, $job_id);
        
        $this->createWorker('hydrogen.worker.simplytestable.com');   

        $taskIds = $this->getTaskIds($canonicalUrl, $job_id);
        $task = $this->getTaskService()->getById($taskIds[0]);
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertEquals(2, $this->runConsole('simplytestable:task:assign-selected'));
       
    } 
     
    
    public function testExecutekInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));                
        $this->assertEquals(-1, $this->runConsole('simplytestable:task:assign-selected'));      
    }

}