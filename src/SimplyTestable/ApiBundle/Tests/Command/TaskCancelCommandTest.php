<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class TaskCancelCommandTest extends BaseSimplyTestableTestCase {    
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }     

    public function testCancelValidTaskReturnsStatusCode0() {
        $worker = $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        
        $task = $this->getTaskService()->getById($taskIds[0]);        
        
        $cancellableStates = array(
            $this->getTaskService()->getAwaitingCancellationState(),
            $this->getTaskService()->getInProgressState(),
            $this->getTaskService()->getQueuedState(),
            $this->getTaskService()->getQueuedForAssignmentState()         
        );
        
        foreach ($cancellableStates as $state) {
            $task->setWorker($worker);
            $task->setState($state);
            $this->getTaskService()->getEntityManager()->persist($task);
            $this->getTaskService()->getEntityManager()->flush();
            
            $result = $this->runConsole('simplytestable:task:cancel', array(
                $taskIds[0] =>  true,
                $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
            ));

            $this->assertEquals(0, $result);
            $this->assertEquals('task-cancelled', $task->getState()->getName());            
        }
    }
  
    
    public function testCancelTaskThatDoesNotExistReturnsStatusCodeMinus1() {      
        //$this->resetSystemState();
        $this->assertEquals(-1, $this->runConsole('simplytestable:task:cancel', array(
            -1 =>  true
        )));
    }
               
    
    public function testCancelTaskInWrongStateReturnsStatusCodeMinus2() {      
        //$this->resetSystemState();
        
        $worker = $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        
        $task = $this->getTaskService()->getById($taskIds[0]);        
        
        $uncancellableStates = array(
            $this->getTaskService()->getCancelledState(),
            $this->getTaskService()->getCompletedState(),
            $this->getTaskService()->getFailedNoRetryAvailableState(),
            $this->getTaskService()->getFailedRetryAvailableState(),
            $this->getTaskService()->getFailedRetryLimitReachedState()

        );
        
        foreach ($uncancellableStates as $state) {
            $task->setWorker($worker);
            $task->setState($state);
            $this->getTaskService()->getEntityManager()->persist($task);
            $this->getTaskService()->getEntityManager()->flush();
            
            $result = $this->runConsole('simplytestable:task:cancel', array(
                $taskIds[0] =>  true,
                $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
            ));

            $this->assertEquals(-2, $result);
            $this->assertEquals($task->getState()->getName(), $task->getState()->getName());            
        } 
    }
    
    
    public function testCancelTaskWhenWorkerIsInReadOnlyModeReturnsStatusCode503() {        
        $worker = $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        
        $task = $this->getTaskService()->getById($taskIds[0]);
        $task->setState($this->getTaskService()->getQueuedState());
        $task->setWorker($worker);
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
            
        $result = $this->runConsole('simplytestable:task:cancel', array(
            $taskIds[0] =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));

        $this->assertEquals(503, $result);
        $this->assertEquals('task-cancelled', $task->getState()->getName());
    }
    
    public function testCancelInReadOnlyModeReturnsStatusCodeMinus3() {
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));
        $this->assertEquals(-3, $this->runConsole('simplytestable:task:cancel', array(
            1 =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        )));
    }    

}
