<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class AssignCommandTest extends BaseSimplyTestableTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }    

    public function testAssignValidTaskReturnsStatusCode0() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        

        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign', array(
            $taskIds[0] =>  true
        )));
        
        $job = json_decode($this->fetchJob($canonicalUrl, $job_id)->getContent());
        $this->assertEquals('in-progress', $job->state);
    }
    
    
    public function testAssignTaskInWrongStateReturnsStatusCode1() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $task->setState($this->getTaskService()->getCompletedState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertEquals(1, $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        )));   
    }
    
    public function testAssignTaskWhenNoWorkersReturnsStatusCode2() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        
        $this->assertEquals(2, $this->runConsole('simplytestable:task:assign', array(
            $taskIds[0] =>  true
        )));
        
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $taskIds[0]
            )
        ));          
    }    
    
    
    public function testAssignTaskWhenNoWorkersAreAvailableReturnsStatusCode3() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        $this->createWorker('http://lithium.worker.simplytestable.com');
        $this->createWorker('http://helium.worker.simplytestable.com');

        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);
        
        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $task->setState($this->getTaskService()->getQueuedState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertEquals(3, $this->runConsole('simplytestable:task:assign', array(
            $taskIds[0] =>  true
        )));
        
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $taskIds[0]
            )
        ));         
    }     
    
    
    public function testAssignInvalidTaskReturnsStatusCode4() {
        $result = $this->runConsole('simplytestable:task:assign', array(
            -1 =>  true
        ));
        
        $this->assertEquals($result, 4);    
    }


    public function testAssignInMaintenanceReadOnlyModeReturnsStatusCode5() {
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));         
        $this->assertEquals(5, $this->runConsole('simplytestable:task:assign', array(
            1 =>  true
        )));
    } 
    
    public function testAssignRaisesHttpClientErrorWithOnlyOneWorker() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');

        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);
        
        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $task->setState($this->getTaskService()->getQueuedState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertEquals(3, $this->runConsole('simplytestable:task:assign', array(
            $taskIds[0] =>  true
        )));
        
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $taskIds[0]
            )
        ));         
    } 
    
    public function testAssignRaisesHttpServerErrorWithOnlyOneWorker() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');

        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);
        
        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $task->setState($this->getTaskService()->getQueuedState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertEquals(3, $this->runConsole('simplytestable:task:assign', array(
            $taskIds[0] =>  true
        )));
        
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $taskIds[0]
            )
        ));         
    } 
    
    public function testAssignTaskWithInProgressEquivalentDoesNotAssignAndInsteadMarksAsInProgress() {
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
    
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign', array(
            $tasks[0]->getId() =>  true
        )));
        
        $this->assertEquals(1, $this->runConsole('simplytestable:task:assign', array(
            $tasks[0]->getId() =>  true
        )));        
        
        foreach ($tasks as $task) {
            $this->assertEquals('task-in-progress', $task->getState());
        }       
    }
    
    
    public function testAssignFirstTaskOfJobDoesNotBreakRemainingTaskUrls() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker();
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl, null, 'full site', array('Link integrity'))->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        

        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign', array(
            $taskIds[0] =>  true
        )));
        
        $job = $this->getJobService()->getById($job_id);
        $tasks = $job->getTasks();
        
        $taskUrls = array();
        foreach ($tasks as $task) {
            $taskUrls[] = $task->getUrl();
        } 
        
        $this->assertEquals(array(
            'http://example.com/',
            'http://example.com/articles/',
            'http://example.com/articles/i-make-the-internet/'
        ), $taskUrls);    
    }
    
}
