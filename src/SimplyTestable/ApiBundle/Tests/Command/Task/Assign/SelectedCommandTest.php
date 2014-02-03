<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class SelectedCommandTest extends ConsoleCommandTestCase {
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:assign-selected';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Task\Assign\SelectedCommand()
        );
    } 
    
    public function testAssignValidTaskReturnsStatusCode0() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->createWorker();
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));

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
    
    
    public function testAssignSelectionMarksEquivalentTasksAsInProgress() {
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
        
        $task = $job1->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertReturnCode(0); 
        
        $this->assertEquals($this->getTaskService()->getInProgressState(), $job1->getTasks()->get(0)->getState());
        $this->assertEquals($this->getTaskService()->getInProgressState(), $job2->getTasks()->get(1)->getState());      
        
        $this->assertEquals($this->getJobService()->getInProgressState(), $job1->getState());
        $this->assertEquals($this->getJobService()->getInProgressState(), $job2->getState());     
    }  

}