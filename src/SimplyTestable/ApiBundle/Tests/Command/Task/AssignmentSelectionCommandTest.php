<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class AssignmentSelectionCommandTest extends BaseSimplyTestableTestCase {    
    const WORKER_TASK_ASSIGNMENT_FACTOR = 2;   
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }    
    
    public function setUp() {
        parent::setUp();        
    }

    public function testSelectTasksForAssignmentWithNoWorkers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->runForNWorkers(0);
    }     
    
    public function testSelectTasksForAssignmentWithOneWorkers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->runForNWorkers(1);
    }    
    
    public function testSelectTasksForAssignmentWithTwoWorkers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->runForNWorkers(2);
    }
    
    public function testSelectTasksForAssignmentWithThreeWorkers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->runForNWorkers(3);
    }
    
    public function testSelectTasksForAssignmentWithFourWorkers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->runForNWorkers(4);
    }
    
    public function testSelectTasksForAssignmentWithFiveWorkers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->runForNWorkers(5);
    }    

    public function testSelectTasksForAssignmentWithSixWorkers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->runForNWorkers(6);
    } 
    
    public function testSelectTasksForAssignmentWithSevenWorkers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->runForNWorkers(7);
    } 
    
    public function testSelectTasksForAssignmentWithEightWorkers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->runForNWorkers(8);
    } 
    
    public function testSelectTasksForAssignmentWithNineWorkers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->runForNWorkers(9);
    } 
    
    public function testSelectTasksForAssignmentWithTenWorkers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->runForNWorkers(10);
    }
    
    
    public function testSelectTasksInMaintenanceReadOnlyModeReturnsStatusCode1() {        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));
        $this->assertEquals(1, $this->runConsole('simplytestable:task:assign:select'));      
    }
    
    
    private function runForNWorkers($requestedWorkerCount) {
//        $this->removeAllWorkers();
//        $this->removeAllJobs();
//        $this->clearRedis();      
        
        $canonicalUrl = 'http://example.com/';   
        
        $jobCreateResponse = $this->createJob($canonicalUrl);        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertInternalType('integer', $job_id);
        $this->assertGreaterThan(0, $job_id);        
        
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
        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign:select'));
        
        $jobResponse = $this->fetchJob($canonicalUrl, $job_id);        
        $jobObject = json_decode($jobResponse->getContent());
        
        $this->assertEquals(200, $jobResponse->getStatusCode());
        $this->assertEquals($expectedQueuedTaskCount, $jobObject->task_count_by_state->{'queued'});
        $this->assertEquals($expectedSelectedTaskCount, $jobObject->task_count_by_state->{'queued-for-assignment'});
        
        $taskIds = $this->getTaskIds($canonicalUrl, $job_id);
        $taskIdGroups = $this->getTaskIdGroups($taskIds, $workerCount, $expectedSelectedTaskCount);

        foreach ($taskIdGroups as $taskIdGroup) {
            $this->assertTrue($this->getResqueQueueService()->contains(
                'SimplyTestable\ApiBundle\Resque\Job\TaskAssignCollectionJob',
                'task-assign-collection',
                array(
                    'ids' => implode(',', $taskIdGroup)
                )
            ));            
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
