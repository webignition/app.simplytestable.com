<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class TaskCancelCollectionCommandTest extends BaseSimplyTestableTestCase {    

    public function testCancelCollectionWithOneWorkerReturnsStatusCode0() {        
        $this->resetSystemState();
        
        $worker = $this->createWorker('hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());
        $tasks = array();
        
        foreach ($taskIds as $taskId) {            
            $task = $this->getTaskService()->getById($taskId); 
            $tasks[] = $task;
            $task->setWorker($worker);
            $this->getTaskService()->getEntityManager()->persist($task);                        
        }
        
        $this->getTaskService()->getEntityManager()->flush();
        
        $result = $this->runConsole('simplytestable:task:cancelcollection', array(
            implode(',', $taskIds) =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(0, $result);
        foreach ($tasks as $task) {
            $this->assertEquals('task-cancelled', $task->getState()->getName());
        }
    }
    
    
    public function testCancelInMaintenanceReadOnlyModeReturnsStatusCode1() {        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));        
        $this->assertEquals(1, $this->runConsole('simplytestable:task:cancelcollection', array(
            implode(',', array(1,2,3)) =>  true
        )));
    }    

}
