<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class AssignCollectionCommandTest extends BaseSimplyTestableTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    } 
    
    public function setUp() {
        parent::setUp();
        $this->removeAllJobs();
        $this->removeAllTasks();
        $this->removeAllWorkers();         
    }
    

    public function testAssignValidTaskReturnsStatusCode0() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));       
        
        $workerHostname = 'hydrogen.worker.simplytestable.com';
        
        $this->createWorker($workerHostname);        
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assigncollection', array(
            implode($taskIds, ',') =>  true
        )));
        
        $tasks = json_decode($this->getJobController('tasksAction')->tasksAction($canonicalUrl, $job_id)->getContent());
        
        foreach ($tasks as $task) {
            $this->assertEquals($workerHostname, $task->worker);
            $this->assertEquals('in-progress', $task->state);
        }
    }
    
    public function testAssignTaskWhenNoWorkersReturnsStatusCode1() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());
        $result = $this->runConsole('simplytestable:task:assigncollection', array(
            implode($taskIds, ',') =>  true
        ));       
        
        $this->assertEquals(1, $result);    
  
        $containsResult = $this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignCollectionJob',
            'task-assign-collection',
            array(
                'ids' => implode(',', $taskIds)
            )
        );
        
        $this->assertTrue($containsResult);        
    }
    
    
    public function testAssignTaskWhenNoWorkersAreAvailableReturnsStatusCode2() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));        
        
        $this->createWorker('hydrogen.worker.simplytestable.com');
        $this->createWorker('lithium.worker.simplytestable.com');
        $this->createWorker('helium.worker.simplytestable.com');        
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());

        $result = $this->runConsole('simplytestable:task:assigncollection', array(
            implode($taskIds, ',') =>  true
        ));
        
        $this->assertEquals(2, $result);
        
        $containsResult = $this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignCollectionJob',
            'task-assign-collection',
            array(
                'ids' => implode(',', $taskIds)
            )
        );
        
        $this->assertTrue($containsResult);        
    } 
     
    
    public function testAssignTaskInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));        
        
        $this->assertEquals(-1, $this->runConsole('simplytestable:task:assigncollection', array(
            implode(array(1,2,3), ',') =>  true
        )));      
    }
    
    
    public function testAssignSingleWorkerWhichRaisesHttpClientErrorReturnsStatusCode2() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('hydrogen.worker.simplytestable.com');     
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());

        $result = $this->runConsole('simplytestable:task:assigncollection', array(
            implode($taskIds, ',') =>  true
        ));
        
        $this->assertEquals(2, $result);
        
        $containsResult = $this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignCollectionJob',
            'task-assign-collection',
            array(
                'ids' => implode(',', $taskIds)
            )
        );
        
        $this->assertTrue($containsResult);         
    }
    
    
    public function testAssignSingleWorkerWhichRaisesHttpServerErrorReturnsStatusCode2() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));        
        
        $this->createWorker('hydrogen.worker.simplytestable.com');     
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());

        $result = $this->runConsole('simplytestable:task:assigncollection', array(
            implode($taskIds, ',') =>  true
        ));
        
        $this->assertEquals(2, $result);
        
        $containsResult = $this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignCollectionJob',
            'task-assign-collection',
            array(
                'ids' => implode(',', $taskIds)
            )
        );
        
        $this->assertTrue($containsResult);         
    }    

}