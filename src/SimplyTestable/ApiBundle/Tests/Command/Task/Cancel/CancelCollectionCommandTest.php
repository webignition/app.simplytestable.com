<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Cancel;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class CancelCollectionCommandTest extends ConsoleCommandTestCase {    
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:cancelcollection';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand(),
            new \SimplyTestable\ApiBundle\Command\TaskCancelCollectionCommand()
        );
    }

    public function testCancelCollectionWithOneWorkerReturnsStatusCode0() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));      
        
        $worker = $this->createWorker('hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());
        $tasks = array();
        
        foreach ($taskIds as $taskId) {            
            $task = $this->getTaskService()->getById($taskId); 
            $task->setState($this->getTaskService()->getQueuedState());
            $tasks[] = $task;
            $task->setWorker($worker);
            $this->getTaskService()->getEntityManager()->persist($task);                        
        }
        
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertReturnCode(0, array(
            'ids' => implode(',', $taskIds)
        ));
        
        foreach ($tasks as $task) {
            $this->assertEquals('task-cancelled', $task->getState()->getName());
        }
    }
    
    
    public function testCancelInMaintenanceReadOnlyModeReturnsStatusCode1() {        
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(1, array(
            'ids' => implode(',', array(1,2,3))
        ));
    } 
    
    
    public function testCancelRaisesHttpClientErrorWithOnlyOneWorker() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));      
        
        $worker = $this->createWorker('hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());
        $tasks = array();
        
        foreach ($taskIds as $taskId) {            
            $task = $this->getTaskService()->getById($taskId); 
            $task->setState($this->getTaskService()->getQueuedState());
            $tasks[] = $task;
            $task->setWorker($worker);
            $this->getTaskService()->getEntityManager()->persist($task);                        
        }
        
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertReturnCode(0, array(
            'ids' => implode(',', $taskIds)
        ));        
        
        foreach ($tasks as $task) {
            $this->assertEquals('task-cancelled', $task->getState()->getName());
        }        
    } 
    
    
    public function testCancelRaisesHttpServerErrorWithOnlyOneWorker() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));      
        
        $worker = $this->createWorker('hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());
        $tasks = array();
        
        foreach ($taskIds as $taskId) {            
            $task = $this->getTaskService()->getById($taskId); 
            $task->setState($this->getTaskService()->getQueuedState());
            $tasks[] = $task;
            $task->setWorker($worker);
            $this->getTaskService()->getEntityManager()->persist($task);                        
        }
        
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertReturnCode(0, array(
            'ids' => implode(',', $taskIds)
        ));
        
        foreach ($tasks as $task) {
            $this->assertEquals('task-cancelled', $task->getState()->getName());
        }        
    }   

}
