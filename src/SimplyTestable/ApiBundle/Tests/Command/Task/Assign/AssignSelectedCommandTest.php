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
    
    
    public function testAssignSelectionMarksEquivalentTasksAsInProgress() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');        
        $canonicalUrl = 'http://example.com/'; 
        
        $jobIds = array();
        
        $jobIds[] = $this->getJobIdFromUrl(
            $this->createJob(
                $canonicalUrl,
                null,
                'full site',
                array(
                    'CSS validation'
                ),
                array(
                    'CSS validation' => array(
                        'ignore-warnings' => 1,
                        'ignore-common-cdns' => 1,
                        'vendor-extensions' => 'warn'
                    )
                )
             )->getTargetUrl()
        );
        
        $jobIds[] = $this->getJobIdFromUrl(
            $this->createJob(
                $canonicalUrl,
                null,
                'full site',
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
                )
             )->getTargetUrl()
        );     
        
        $taskIds = array();
        
        foreach ($jobIds as $job_id) {
             $this->prepareJob($canonicalUrl, $job_id);
             $taskIds[$job_id] = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());
        }
        
        $tasks = array(
            $this->getTaskService()->getById($taskIds[$jobIds[0]][0]),
            $this->getTaskService()->getById($taskIds[$jobIds[1]][1])
        );
        
        $tasks[0]->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getEntityManager()->persist($tasks[0]);
        $this->getTaskService()->getEntityManager()->flush();
    
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign-selected'));
     
        foreach ($tasks as $task) {
            $this->assertEquals('task-in-progress', $task->getState()->getName());
            $this->assertEquals('job-in-progress', $task->getJob()->getState()->getName());
        }       
    }    

}