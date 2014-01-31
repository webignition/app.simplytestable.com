<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Cancel;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class CancelCommandTest extends ConsoleCommandTestCase {    
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:cancel';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand(),            
            new \SimplyTestable\ApiBundle\Command\TaskCancelCommand()
        );
    }   

    public function testCancelValidTaskReturnsStatusCode0() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
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
            
            $this->assertReturnCode(0, array(
                'id' => $taskIds[0]
            ));

            $this->assertEquals('task-cancelled', $task->getState()->getName());            
        }
    }
  
    
    public function testCancelTaskThatDoesNotExistReturnsStatusCodeMinus1() {
        $this->assertReturnCode(-1, array(
            'id' => -1
        ));
    }
               
    
    public function testCancelTaskInWrongStateReturnsStatusCodeMinus2() {      
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
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
            
            $this->assertReturnCode(-2, array(
                'id' => $taskIds[0]
            ));            

            $this->assertEquals($task->getState()->getName(), $task->getState()->getName());            
        } 
    }
    
    
    public function testCancelTaskWhenWorkerIsInReadOnlyModeReturnsStatusCode503() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
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
        
        $this->assertReturnCode(503, array(
            'id' => $taskIds[0]
        ));        

        $this->assertEquals('task-awaiting-cancellation', $task->getState()->getName());
    }
    
    public function testCancelInReadOnlyModeReturnsStatusCodeMinus3() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');        
        $this->assertReturnCode(-3, array(
            'id' => 1
        ));
    }    
    
    
    public function testCancelRaisesHttpClientErrorWithOnlyOneWorker() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
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
            
            $this->assertReturnCode(404, array(
                'id' => $taskIds[0]
            ));            
            $this->assertEquals('task-awaiting-cancellation', $task->getState()->getName());            
        }       
    } 
    
    
    public function testCancelRaisesHttpServerErrorWithOnlyOneWorker() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
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
            
            $this->assertReturnCode(503, array(
                'id' => $taskIds[0]
            ));            
            
            $this->assertEquals('task-awaiting-cancellation', $task->getState()->getName());            
        }       
    }     

}
