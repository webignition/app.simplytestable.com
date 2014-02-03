<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class CommandTest extends ConsoleCommandTestCase {
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:assign';
    }  

    public function testAssignValidTaskReturnsStatusCode0() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->createWorker();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        
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
    
    public function testAssignTaskWithInProgressEquivalentDoesNotAssignAndInsteadMarksAsInProgress() {
        $this->createWorker();        
        
        $job1 = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site',
            array(
                'CSS validation'
            ),
            array(
                'CSS validation' => array(
                    'ignore-warnings' => 1,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
            )
        )));
        
        $job2 = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site',
            array(
                'HTML validation',
                'CSS validation'
            ),
            array(
                'CSS validation' => array(
                    'ignore-warnings' => 1,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
            )
        )));        
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        
        $this->assertReturnCode(0, array(
            'id' => $job1->getTasks()->get(0)->getId()
        ));    
        
        $this->assertReturnCode(1, array(
            'id' => $job2->getTasks()->get(1)->getId()
        )); 
        
        $this->assertEquals($this->getTaskService()->getInProgressState(), $job1->getTasks()->get(0)->getState());
        $this->assertEquals($this->getTaskService()->getInProgressState(), $job2->getTasks()->get(1)->getState());      
    }
    
    
    public function testAssignFirstTaskOfJobDoesNotBreakRemainingTaskUrls() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->createWorker();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));     
        
        $this->assertReturnCode(0, array(
            'id' => $job->getTasks()->first()->getId()
        ));  
        
        $expectedTaskUrls = array(
            'http://example.com/',
            'http://example.com/one/',
            'http://example.com/two/'            
        );
        
        foreach ($job->getTasks() as $task) {
            $this->assertTrue(in_array($task->getUrl(), $expectedTaskUrls));
        }   
    }
    
}
