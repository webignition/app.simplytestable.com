<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class TaskAssignmentSelectionCommandTest extends BaseSimplyTestableTestCase {    
    const WORKER_TASK_ASSIGNMENT_FACTOR = 2;    

    public function testSelectTasksForAssignmentWithNoWorkers() {
        $this->runForNWorkers(0);
    }     
    
    public function testSelectTasksForAssignmentWithOneWorkers() {
        $this->runForNWorkers(1);
    }    
    
    public function testSelectTasksForAssignmentWithTwoWorkers() {
        $this->runForNWorkers(2);
    }
    
    public function testSelectTasksForAssignmentWithThreeWorkers() {
        $this->runForNWorkers(3);
    }
    
    public function testSelectTasksForAssignmentWithFourWorkers() {
        $this->runForNWorkers(4);
    }
    
    public function testSelectTasksForAssignmentWithFiveWorkers() {
        $this->runForNWorkers(5);
    }    

    public function testSelectTasksForAssignmentWithSixWorkers() {
        $this->runForNWorkers(6);
    } 
    
    public function testSelectTasksForAssignmentWithSevenWorkers() {
        $this->runForNWorkers(7);
    } 
    
    public function testSelectTasksForAssignmentWithEightWorkers() {
        $this->runForNWorkers(8);
    } 
    
    public function testSelectTasksForAssignmentWithNineWorkers() {
        $this->runForNWorkers(9);
    } 
    
    public function testSelectTasksForAssignmentWithTenWorkers() {
        $this->runForNWorkers(10);
    }     
    
    
    private function runForNWorkers($requestedWorkerCount) {        
        $this->resetSystemState();     
        
        $canonicalUrl = 'http://example.com/';   
        
        $jobCreateResponse = $this->createJob($canonicalUrl);        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertEquals(1, $job_id);        
        
        $this->prepareJob($canonicalUrl, $job_id);        
        
        $preSelectionJobResponse = $this->fetchJob($canonicalUrl, $job_id);
        $taskCount = json_decode($preSelectionJobResponse->getContent())->task_count;
        
        $this->createWorkers($requestedWorkerCount);   
        
        $workerCount = $this->getWorkerService()->count();
        $expectedSelectedTaskCount = $workerCount * self::WORKER_TASK_ASSIGNMENT_FACTOR;
        if ($expectedSelectedTaskCount > $taskCount) {
            $expectedSelectedTaskCount = $taskCount;
        }
        
        $expectedQueuedTaskCount =  $taskCount - $expectedSelectedTaskCount;       
        if ($expectedQueuedTaskCount < 0) {
            $expectedQueuedTaskCount = 0;
        }
        
        $this->runConsole('simplytestable:task:assign:select');
        
        $jobResponse = $this->fetchJob($canonicalUrl, $job_id);        
        $jobObject = json_decode($jobResponse->getContent());
        
        $this->assertEquals(200, $jobResponse->getStatusCode());
        $this->assertEquals($expectedQueuedTaskCount, $jobObject->task_count_by_state->{'queued'});
        $this->assertEquals($expectedSelectedTaskCount, $jobObject->task_count_by_state->{'queued-for-assignment'});
        
        $taskIds = $this->getTaskIds($canonicalUrl, $job_id);
        $taskIdGroups = $this->getTaskIdGroups($taskIds, $workerCount, $expectedSelectedTaskCount);

        foreach ($taskIdGroups as $taskIdGroup) {
            $containsResult = $this->getResqueQueueService()->contains(
                'SimplyTestable\ApiBundle\Resque\Job\TaskAssignCollectionJob',
                'task-assign-collection',
                array(
                    'ids' => implode(',', $taskIdGroup)
                )
            );

            $this->assertTrue($containsResult);            
        }
    }    
    
    
    
    /**
     * 
     * @param array $tasks
     * @param int $groupCount
     * @param int $limit
     * @return array
     */
    private function getTaskIdGroups($taskIds, $groupCount, $limit = null) {
        $taskIdGroups = array();
        $groupIndex = 0;
        $maximumGroupIndex = $groupCount - 1;
        $selectedCount = 0;
        
        if (is_null($limit)) {
            $limit = count($taskIds);
        }
        
        foreach ($taskIds as $taskId) {
            $selectedCount++;
            
            if ($selectedCount <= $limit) {                
                if (!isset($taskIdGroups[$groupIndex])) {
                    $taskIdGroups[$groupIndex] = array();
                }

                $taskIdGroups[$groupIndex][] = $taskId;

                $groupIndex++;
                if ($groupIndex > $maximumGroupIndex) {
                    $groupIndex = 0;
                }
            }
        }
        
        return $taskIdGroups;
    }     
    
    



}
