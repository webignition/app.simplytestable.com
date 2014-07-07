<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign\SelectedCommand;

class DefaultTest extends CommandTest {

    
    public function testAssignValidTaskReturnsStatusCode0() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->createWorker();
        
        $this->queueTaskAssignCollectionResponseHttpFixture();

        $task = $job->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();        
        
        $this->assertEquals(1, json_decode($this->fetchJobResponse($job)->getContent())->task_count_by_state->{'queued-for-assignment'});
        
        $this->assertReturnCode(0);        

        $postAssignJobObject = json_decode($this->fetchJobResponse($job)->getContent());
        
        $this->assertEquals(0, $postAssignJobObject->task_count_by_state->{'queued-for-assignment'});
        $this->assertEquals(1, $postAssignJobObject->task_count_by_state->{'in-progress'});      
    }

    
    public function testAssignTaskWhenNoWorkersReturnsStatusCode1() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        
        $task = $job->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertReturnCode(1);   
    }

    
    public function testAssignTaskWhenNoWorkersAreAvailableReturnsStatusCode2() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',          
        )));        
        
        $this->createWorker('hydrogen.worker.simplytestable.com');

        $task = $job->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();        
        
        $this->assertReturnCode(2);        
    } 
     
    
    public function testExecutekInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {        
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(-1);      
    }

}