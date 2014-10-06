<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Cancel\Command;

class CancelCommandTest extends BaseTest {    
    
    public function testCancelTaskThatDoesNotExistReturnsStatusCodeMinus1() {
        $this->assertReturnCode(-1, array(
            'id' => -1
        ));
    }   
    
    
    public function testCancelInReadOnlyModeReturnsStatusCodeMinus3() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');        
        $this->assertReturnCode(-3, array(
            'id' => 1
        ));
    }      
    

    public function testCancelValidTaskReturnsStatusCode0() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 200',
            'HTTP/1.0 200',
            'HTTP/1.0 200',
            'HTTP/1.0 200',
        )));
                
        $worker = $this->createWorker();
        
        $task = $job->getTasks()->first();
        $cancellableStates = array(
            $this->getTaskService()->getAwaitingCancellationState(),
            $this->getTaskService()->getInProgressState(),
            $this->getTaskService()->getQueuedState(),
            $this->getTaskService()->getQueuedForAssignmentState()         
        );
        
        foreach ($cancellableStates as $state) {
            $task->setWorker($worker);
            $task->setState($state);
            $this->getTaskService()->getManager()->persist($task);
            $this->getTaskService()->getManager()->flush();
            
            $this->assertReturnCode(0, array(
                'id' => $task->getId()
            ));

            $this->assertEquals('task-cancelled', $task->getState()->getName());            
        }
    }
               
    
    public function testCancelTaskInWrongStateReturnsStatusCodeMinus2() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $worker = $this->createWorker();
        
        $task = $job->getTasks()->first();
        
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
            $this->getTaskService()->getManager()->persist($task);
            $this->getTaskService()->getManager()->flush();
            
            $this->assertReturnCode(-2, array(
                'id' => $task->getId()
            ));            

            $this->assertEquals($task->getState()->getName(), $task->getState()->getName());            
        } 
    }
    
    
    public function testCancelTaskWhenWorkerIsInReadOnlyModeReturnsStatusCode503() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 503',
        )));        
        
        $worker = $this->createWorker();

        $task = $job->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedState());
        $task->setWorker($worker);
        $this->getTaskService()->getManager()->persist($task);
        $this->getTaskService()->getManager()->flush();
        
        $this->assertReturnCode(503, array(
            'id' => $task->getId()
        ));        

        $this->assertEquals('task-awaiting-cancellation', $task->getState()->getName());
    }  

}
