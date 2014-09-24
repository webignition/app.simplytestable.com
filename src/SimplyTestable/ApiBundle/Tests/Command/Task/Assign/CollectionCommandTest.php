<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class CollectionCommandTest extends ConsoleCommandTestCase {   
    
    const CANONICAL_URL = 'http://example.com/';
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:assigncollection';
    }    

    public function testAssignValidTaskReturnsStatusCode0() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        $this->createWorker();

        $this->assertReturnCode(0, array(
            'ids' => implode($this->getTaskIds($job), ',')
        ));
        
        $this->assertGreaterThan(0, $job->getTasks()->count());
     
        foreach ($job->getTasks() as $task) {
            $this->assertNotNull($task->getWorker());
            $this->assertEquals($this->getTaskService()->getInProgressState(), $task->getState());
        }
    }
    
    public function testAssignTaskWhenNoWorkersReturnsStatusCode1() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());

        $taskIds = $this->getTaskIds($job);
    
        $this->assertReturnCode(1, array(
            'ids' => implode($taskIds, ',')
        ));        

        $containsResult = $this->getResqueQueueService()->contains(
            'task-assign-collection',
            array(
                'ids' => implode(',', $taskIds)
            )
        );
        
        $this->assertTrue($containsResult);        
    }
    
    
    public function testAssignTaskWhenNoWorkersAreAvailableReturnsStatusCode2() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',          
        )));        
        
        $this->createWorker('hydrogen.worker.simplytestable.com');
        $this->createWorker('lithium.worker.simplytestable.com');
        $this->createWorker('helium.worker.simplytestable.com');        

        $taskIds = $this->getTaskIds($job);
        
        $this->assertReturnCode(2, array(
            'ids' => implode($taskIds, ',')
        ));        
        
        $containsResult = $this->getResqueQueueService()->contains(
            'task-assign-collection',
            array(
                'ids' => implode(',', $taskIds)
            )
        );
        
        $this->assertTrue($containsResult);        
    } 
     
    
    public function testAssignTaskInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {        
        $this->executeCommand('simplytestable:maintenance:enable-read-only');

        $this->assertReturnCode(-1, array(
            'ids' => implode(array(1,2,3), ',')
        ));
    }  

}