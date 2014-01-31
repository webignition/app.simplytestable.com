<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class AssignCollectionCommandTest extends ConsoleCommandTestCase {   
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:assigncollection';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\TaskAssignCollectionCommand()
        );
    }     

    public function testAssignValidTaskReturnsStatusCode0() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));       
        
        $workerHostname = 'hydrogen.worker.simplytestable.com';
        
        $this->createWorker($workerHostname);        
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        
        $this->assertReturnCode(0, array(
            'ids' => implode($taskIds, ',')
        ));
        
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
        $this->assertReturnCode(1, array(
            'ids' => implode($taskIds, ',')
        ));    
  
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
        
        $this->assertReturnCode(2, array(
            'ids' => implode($taskIds, ',')
        ));        
        
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
        $this->executeCommand('simplytestable:maintenance:enable-read-only');

        $this->assertReturnCode(-1, array(
            'ids' => implode(array(1,2,3), ',')
        ));
    }
    
    
    public function testAssignSingleWorkerWhichRaisesHttpClientErrorReturnsStatusCode2() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('hydrogen.worker.simplytestable.com');     
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());

        $this->assertReturnCode(2, array(
            'ids' => implode($taskIds, ',')
        )); 
        
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

        $this->assertReturnCode(2, array(
            'ids' => implode($taskIds, ',')
        )); 
        
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