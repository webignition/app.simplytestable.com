<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class EnqueuePrepareAllCommandTest extends ConsoleCommandTestCase {    
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:job:enqueue-prepare-all';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand(),
            new \SimplyTestable\ApiBundle\Command\Job\EnqueuePrepareAllCommand()
        );
    }    
    
    public function testJobsAreEnqueued() {      
        $canonicalUrls = array(
            'http://one.example.com/',
            'http://two.example.com/'
        );
        
        $jobIds = array();
        foreach ($canonicalUrls as $canonicalUrl) {
            $jobIds[] = $this->createJobAndGetId($canonicalUrl);
        }
        
        $this->assertReturnCode(0);       
        
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
    
    
    public function testExecuteInMaintenanceReadOnlyModeReturnsStatusCode1() {     
        $this->executeCommand('simplytestable:maintenance:enable-read-only');        
        $this->assertReturnCode(1);
    }

}
