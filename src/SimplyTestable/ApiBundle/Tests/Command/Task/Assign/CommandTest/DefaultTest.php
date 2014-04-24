<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign\CommandTest;

class DefaultTest extends CommandTest {

    public function testAssignValidTaskReturnsStatusCode0() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->createWorker();
        
        $this->queueTaskAssignResponseHttpFixture();        
        $this->assertReturnCode(0, array(
            'id' => $job->getTasks()->first()->getId()
        ));
        
        $this->assertEquals($this->getJobService()->getInProgressState(), $job->getState());
    }
    
    
    public function testAssignTaskInWrongStateReturnsStatusCode1() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->createWorker();
        
        $task = $job->getTasks()->first();
        
        $task->setState($this->getTaskService()->getCompletedState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertReturnCode(1, array(
            'id' => $task->getId()
        )); 
    }
    
    public function testAssignTaskWhenNoWorkersReturnsStatusCode2() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        
        $this->assertReturnCode(2, array(
            'id' => $job->getTasks()->first()->getId()
        ));
        
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $job->getTasks()->first()->getId()
            )
        ));          
    }    
    
    
    public function testAssignTaskWhenNoWorkersAreAvailableReturnsStatusCode3() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404'
        )));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        $this->createWorker('http://lithium.worker.simplytestable.com');
        $this->createWorker('http://helium.worker.simplytestable.com');

        $this->assertReturnCode(3, array(
            'id' => $job->getTasks()->first()->getId()
        ));        
        
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $job->getTasks()->first()->getId()
            )
        ));         
    }     
    
    
    public function testAssignInvalidTaskReturnsStatusCode4() {        
        $this->assertReturnCode(4, array(
            'id' => -1
        ));         
    }


    public function testAssignInMaintenanceReadOnlyModeReturnsStatusCode5() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');        
        $this->assertReturnCode(5, array(
            'id' => 1
        ));
    }
    
    
    public function testAssignFirstTaskOfJobDoesNotBreakRemainingTaskUrls() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->createWorker();
        
        $this->queueTaskAssignResponseHttpFixture();
        $this->assertReturnCode(0, array(
            'id' => $job->getTasks()->first()->getId()
        ));
        
        foreach ($job->getTasks() as $task) {            
            $this->assertTrue(in_array($task->getUrl(), array(
                'http://example.com/0/',
                'http://example.com/1/',
                'http://example.com/2/'            
            )));
        }   
    }
    
}
