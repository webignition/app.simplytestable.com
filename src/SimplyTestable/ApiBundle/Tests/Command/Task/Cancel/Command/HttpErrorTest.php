<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Cancel\Command;

class HttpErrorTest extends BaseTest {
    
    public function setUp() {
        parent::setUp();      
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 ' . $this->getStatusCode(),
            'HTTP/1.0 ' . $this->getStatusCode(),
            'HTTP/1.0 ' . $this->getStatusCode(),
            'HTTP/1.0 ' . $this->getStatusCode(),
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
            $this->getTaskService()->getEntityManager()->persist($task);
            $this->getTaskService()->getEntityManager()->flush();
            
            $this->assertReturnCode($this->getStatusCode(), array(
                'id' => $task->getId()
            ));            
            $this->assertEquals($this->getTaskService()->getAwaitingCancellationState(), $task->getState());
        }       
    }
    
    public function test400() {}
    public function test404() {}
    public function test500() {}
    public function test503() {}
    
    
    /**
     * 
     * @return int
     */
    private function getStatusCode() {
        return (int)  str_replace('test', '', $this->getName());
    }

}
