<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class CompleteAllWithNoIncompleteTasksCommandTest extends ConsoleCommandTestCase {    
    
    const RETURN_CODE_DONE = 0;
    const RETURN_CODE_IN_MAINTENANCE_MODE = 1;
    const RETURN_CODE_NO_MATCHING_JOBS = 2;
    
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:job:complete-all-with-no-incomplete-tasks';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Job\CompleteAllWithNoIncompleteTasksCommand()
        );
    }
    
    public function testExecuteInMaintenanceReadOnlyModeReturnsStatusCode1() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');        
        $this->assertReturnCode(self::RETURN_CODE_IN_MAINTENANCE_MODE);        
    }     
    
    public function testWithNoJobs() {
        $this->assertReturnCode(self::RETURN_CODE_NO_MATCHING_JOBS);
    }
    
    public function testWithOnlyCrawlJobs() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
      
        $job->setType($this->getJobTypeService()->getCrawlType());
        foreach ($job->getTasks() as $task) {
            $task->setState($this->getTaskService()->getCompletedState());
        }
        
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertReturnCode(self::RETURN_CODE_NO_MATCHING_JOBS);
    }
    
    public function testWithSingleJobWithIncompleteTasks() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        
        $job->setState($this->getJobService()->getInProgressState());        
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertReturnCode(self::RETURN_CODE_NO_MATCHING_JOBS);
        $this->assertEquals($this->getJobService()->getInProgressState(), $job->getState());
    }
    
    public function testWithSingleJobWithNoIncompleteTasks() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        
        foreach ($job->getTasks() as $task) {
            $task->setState($this->getTaskService()->getCompletedState());
        }
        
        $job->setState($this->getJobService()->getInProgressState());        
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertReturnCode(self::RETURN_CODE_DONE);        
        $this->assertEquals($this->getJobService()->getCompletedState(), $job->getState());        
    }
    
    
    public function testWithCollectionOfJobsWithNoIncompleteTasks() {        
        $jobs = array();
        
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://one.example.com/'));
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://two.example.com/'));
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://three.example.com/'));        
     
        foreach ($jobs as $job) {
            foreach ($job->getTasks() as $task) {                
                $task->setState($this->getTaskService()->getCompletedState());                
            }
            
            $this->getJobService()->persistAndFlush($job);
        }
   
        $this->assertReturnCode(self::RETURN_CODE_DONE);
        
        foreach ($jobs as $job) {
            $this->assertEquals($this->getJobService()->getCompletedState(), $job->getState());
        }
    }  
    
    
    public function testWithCollectionOfJobsSomeWithIncompleteTasksAndSomeWithout() {
        $jobs = array();
        
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://one.example.com/'));
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://two.example.com/'));
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://three.example.com/'));    
        
        foreach ($jobs as $jobIndex => $job) {
            if ($jobIndex === 0) {
                continue;
            }
            
            foreach ($job->getTasks() as $task) {
                $task->setState($this->getTaskService()->getCompletedState());                
            }
            
            $this->getJobService()->persistAndFlush($job);
        }
   
        $this->assertReturnCode(self::RETURN_CODE_DONE);
        
        foreach ($jobs as $jobIndex => $job) {
            if ($jobIndex === 0) {
                $this->assertEquals($this->getJobService()->getQueuedState(), $job->getState());
            } else {
                $this->assertEquals($this->getJobService()->getCompletedState(), $job->getState());
            }
        }      
    }   


}
