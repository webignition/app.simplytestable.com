<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

use SimplyTestable\ApiBundle\Entity\Job\Job;

class CompleteAllWithNoIncompleteTasksCommandTest extends BaseSimplyTestableTestCase {    
    
    const RETURN_CODE_DONE = 0;
    const RETURN_CODE_IN_MAINTENANCE_MODE = 1;
    const RETURN_CODE_NO_MATCHING_JOBS = 2;
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
    public function testExecuteInMaintenanceReadOnlyModeReturnsStatusCode1() {     
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));        
        $this->assertEquals(self::RETURN_CODE_IN_MAINTENANCE_MODE, $this->runConsole('simplytestable:job:complete-all-with-no-incomplete-tasks'));  
    }     
    
    public function testWithNoJobs() {
        $this->assertEquals(self::RETURN_CODE_NO_MATCHING_JOBS, $this->runConsole('simplytestable:job:complete-all-with-no-incomplete-tasks'));
    }
    
    public function testWithOnlyCrawlJobs() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));        
        $job = $this->getJobService()->getById($this->createAndPrepareJob('http://example.com'));        
        $job->setType($this->getJobTypeService()->getCrawlType());
        foreach ($job->getTasks() as $task) {
            $task->setState($this->getTaskService()->getCompletedState());
        }
        
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertEquals(self::RETURN_CODE_NO_MATCHING_JOBS, $this->runConsole('simplytestable:job:complete-all-with-no-incomplete-tasks'));
    }
    
    public function testWithSingleJobWithIncompleteTasks() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));        
        $job = $this->getJobService()->getById($this->createAndPrepareJob('http://example.com'));
        
        $job->setState($this->getJobService()->getInProgressState());        
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertEquals(self::RETURN_CODE_NO_MATCHING_JOBS, $this->runConsole('simplytestable:job:complete-all-with-no-incomplete-tasks'));
        $this->assertEquals($this->getJobService()->getInProgressState(), $job->getState());
    }
    
    public function testWithSingleJobWithNoIncompleteTasks() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));        
        $job = $this->getJobService()->getById($this->createAndPrepareJob('http://example.com'));
        
        foreach ($job->getTasks() as $task) {
            $task->setState($this->getTaskService()->getCompletedState());
        }
        
        $job->setState($this->getJobService()->getInProgressState());        
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertEquals(self::RETURN_CODE_DONE, $this->runConsole('simplytestable:job:complete-all-with-no-incomplete-tasks'));
        $this->assertEquals($this->getJobService()->getCompletedState(), $job->getState());        
    }
    
    
    public function testWithCollectionOfJobsWithNoIncompleteTasks() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));        
        $jobs = array();
        
        $jobs[] = $this->getJobService()->getById($this->createAndPrepareJob('http://one.example.com'));
        $jobs[] = $this->getJobService()->getById($this->createAndPrepareJob('http://two.example.com'));
        $jobs[] = $this->getJobService()->getById($this->createAndPrepareJob('http://three.example.com'));
        
        foreach ($jobs as $job) {
            foreach ($job->getTasks() as $task) {
                $task->setState($this->getTaskService()->getCompletedState());                
            }
            
            $this->getJobService()->persistAndFlush($job);
        }
   
        $this->assertEquals(self::RETURN_CODE_DONE, $this->runConsole('simplytestable:job:complete-all-with-no-incomplete-tasks'));
        
        foreach ($jobs as $job) {
            $this->assertEquals($this->getJobService()->getCompletedState(), $job->getState());
        }
    }  
    
    
    public function testWithCollectionOfJobsSomeWithIncompleteTasksAndSomeWithout() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));        
        $jobs = array();
        
        $jobs[] = $this->getJobService()->getById($this->createAndPrepareJob('http://one.example.com'));
        $jobs[] = $this->getJobService()->getById($this->createAndPrepareJob('http://two.example.com'));
        $jobs[] = $this->getJobService()->getById($this->createAndPrepareJob('http://three.example.com'));
        
        foreach ($jobs as $jobIndex => $job) {
            if ($jobIndex === 0) {
                continue;
            }
            
            foreach ($job->getTasks() as $task) {
                $task->setState($this->getTaskService()->getCompletedState());                
            }
            
            $this->getJobService()->persistAndFlush($job);
        }
   
        $this->assertEquals(self::RETURN_CODE_DONE, $this->runConsole('simplytestable:job:complete-all-with-no-incomplete-tasks'));
        
        foreach ($jobs as $jobIndex => $job) {
            if ($jobIndex === 0) {
                $this->assertEquals($this->getJobService()->getQueuedState(), $job->getState());
            } else {
                $this->assertEquals($this->getJobService()->getCompletedState(), $job->getState());
            }
        }      
    }   


}
