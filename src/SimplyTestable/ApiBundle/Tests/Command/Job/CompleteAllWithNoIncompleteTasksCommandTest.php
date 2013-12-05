<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

use SimplyTestable\ApiBundle\Entity\Job\Job;

class CompleteAllWithNoIncompleteTasksCommandTest extends BaseSimplyTestableTestCase {    
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
//    public function testExecuteInMaintenanceReadOnlyModeReturnsStatusCode1() {     
//        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));        
//        $this->assertEquals(1, $this->runConsole('simplytestable:job:complete-all-with-no-incomplete-tasks'));  
//    }     
//    
//    public function testWithNoJobs() {
//        $this->assertEquals(2, $this->runConsole('simplytestable:job:complete-all-with-no-incomplete-tasks'));
//    }
//    
//    public function testWithOnlyCrawlJobs() {
//        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com'));        
//        $job->setType($this->getJobTypeService()->getCrawlType());
//        $this->getJobService()->persistAndFlush($job);
//        
//        $this->assertEquals(2, $this->runConsole('simplytestable:job:complete-all-with-no-incomplete-tasks'));
//    }
    
    public function testWithSingleJobWithIncompleteTasks() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));        
        $job = $this->getJobService()->getById($this->createAndPrepareJob('http://example.com'));
        
        $job->setState($this->getJobService()->getInProgressState());
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertEquals(3, $this->runConsole('simplytestable:job:complete-all-with-no-incomplete-tasks'));
        
//        foreach ($job->getTasks() as $task) {
//            var_dump("cp01");
//        }
    }
    
    // test with only crawl jobs
    // test with only regular jobs
    // test with mix of crawl and regular jobs
    
//    public function testJobsAreEnqueued() {      
//        $canonicalUrls = array(
//            'http://one.example.com/',
//            'http://two.example.com/'
//        );
//        
//        $jobIds = array();
//        foreach ($canonicalUrls as $canonicalUrl) {
//            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
//        }
//        
//        $this->assertEquals(0, $this->runConsole('simplytestable:job:enqueue-prepare-all'));         
//        
//        foreach ($jobIds as $jobId) {
//            $this->assertTrue($this->getResqueQueueService()->contains(
//                'SimplyTestable\ApiBundle\Resque\Job\JobPrepareJob',
//                'job-prepare',
//                array(
//                    'id' => $jobId
//                )
//            ));            
//        }      
//    }
//    
//    


}
