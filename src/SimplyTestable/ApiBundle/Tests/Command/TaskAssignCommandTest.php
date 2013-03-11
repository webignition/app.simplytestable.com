<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class TaskAssignCommandTest extends BaseSimplyTestableTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();
    }        
    

    public function testAssignValidTaskReturnsStatusCode0() {        
        //$this->setupDatabaseIf();
        //$this->resetSystemState();
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        

        $result = $this->runConsole('simplytestable:task:assign', array(
            $taskIds[0] =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $job = json_decode($this->fetchJob($canonicalUrl, $job_id)->getContent());
        
        $this->assertEquals(0, $result);
        $this->assertEquals('in-progress', $job->state);
    }
    
    
    public function testAssignTaskInWrongStateReturnsStatusCode1() {        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');        
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $task->setState($this->getTaskService()->getCompletedState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();

        $result = $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(1, $result);   
    }
    
    public function testAssignTaskWhenNoWorkersReturnsStatusCode2() {
        $this->removeAllWorkers();
        $this->clearRedis();
        //$this->setupDatabase();
        //$this->resetSystemState();
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        

        $result = $this->runConsole('simplytestable:task:assign', array(
            $taskIds[0] =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(2, $result);
        
        $containsResult = $this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $taskIds[0]
            )
        );
        
        $this->assertTrue($containsResult);          
    }    
    
    
    public function testAssignTaskWhenNoWorkersAreAvailableReturnsStatusCode3() {
        $this->removeAllWorkers();
        $this->clearRedis();     
        
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

        $result = $this->runConsole('simplytestable:task:assign', array(
            $taskIds[0] =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(3, $result);

        $containsResult = $this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $taskIds[0]
            )
        );
        
        $this->assertTrue($containsResult);         
    }     
    
    
    public function testAssignInvalidTaskReturnsStatusCode4() {        
        $result = $this->runConsole('simplytestable:task:assign', array(
            -1 =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals($result, 4);    
    }


    public function testAssignInMaintenanceReadOnlyModeReturnsStatusCode5() {
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));         
        $this->assertEquals(5, $this->runConsole('simplytestable:task:assign', array(
            1 =>  true
        )));
    }    
    
}
