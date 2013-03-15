<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class EnqueuePrepareAllCommandTest extends BaseSimplyTestableTestCase {    
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
    public function testJobsAreEnqueued() {
        $this->removeAllJobs();
        $this->createPublicUserIfMissing();
        $this->clearRedis();
      
        $canonicalUrls = array(
            'http://one.example.com/',
            'http://two.example.com/'
        );
        
        $jobIds = array();
        foreach ($canonicalUrls as $canonicalUrl) {
            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
        }
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:enqueue-prepare-all'));         
        
        foreach ($jobIds as $jobId) {
            $this->assertTrue($this->getResqueQueueService()->contains(
                'SimplyTestable\ApiBundle\Resque\Job\JobPrepareJob',
                'job-prepare',
                array(
                    'id' => $jobId
                )
            ));            
        }      
    }
    
    
    public function testExecuteInMaintenanceReadOnlyModeReturnsStatusCode2() {     
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));        
        $this->assertEquals(1, $this->runConsole('simplytestable:job:enqueue-prepare-all'));  
    } 

}
