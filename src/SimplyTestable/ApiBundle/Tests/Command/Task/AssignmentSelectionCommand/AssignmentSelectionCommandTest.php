<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\AssignmentSelectionCommand;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class AssignmentSelectionCommandTest extends ConsoleCommandTestCase {    
    const WORKER_TASK_ASSIGNMENT_FACTOR = 2;
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:assign:select';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Task\AssignmentSelectionCommand()
        );
    }     


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
    
    
    public function testSelectTasksInMaintenanceReadOnlyModeReturnsStatusCode1() {        
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(1);  
    }
    
    
    private function runForNWorkers($requestedWorkerCount) {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());

        $taskCount = $job->getTasks()->count();
        
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
        
        $this->assertReturnCode(0);
        
        $jobResponse = $this->fetchJobResponse($job);
        $jobObject = json_decode($jobResponse->getContent());
        
        $this->assertEquals(200, $jobResponse->getStatusCode());
        $this->assertEquals($expectedQueuedTaskCount, $jobObject->task_count_by_state->{'queued'});
        $this->assertEquals($expectedSelectedTaskCount, $jobObject->task_count_by_state->{'queued-for-assignment'});
        
        $taskIds = $this->getTaskIds($job);
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
