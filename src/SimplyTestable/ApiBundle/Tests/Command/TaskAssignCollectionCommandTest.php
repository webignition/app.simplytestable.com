<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class TaskAssignCollectionCommandTest extends BaseSimplyTestableTestCase {

    public function testAssignValidTaskReturnsStatusCode0() {        
        $this->setupDatabase();
        
        $workerHostname = 'hydrogen.worker.simplytestable.com';
        
        $this->createWorker($workerHostname);
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());

        $result = $this->runConsole('simplytestable:task:assigncollection', array(
            implode($taskIds, ',') =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(0, $result);
        
        $tasks = json_decode($this->getJobController('tasksAction')->tasksAction($canonicalUrl, $job_id)->getContent());
        
        foreach ($tasks as $task) {
            $this->assertEquals($workerHostname, $task->worker);
            $this->assertEquals('in-progress', $task->state);
        }
    }
    
    public function testAssignTaskWhenNoWorkersReturnsStatusCode1() {
        $this->setupDatabase();
        $this->clearRedis();
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());

        $result = $this->runConsole('simplytestable:task:assigncollection', array(
            implode($taskIds, ',') =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
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
        $this->setupDatabase();
        
        $this->createWorker('hydrogen.worker.simplytestable.com');
        $this->createWorker('lithium.worker.simplytestable.com');
        $this->createWorker('helium.worker.simplytestable.com');        
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());

        $result = $this->runConsole('simplytestable:task:assigncollection', array(
            implode($taskIds, ',') =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
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
