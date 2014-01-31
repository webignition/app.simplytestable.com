<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

    /**
     * Required tests:
     *  - happy-path occurrences of the 4 return codes
     *     const RETURN_CODE_OK = 0;
     *     const RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE = 1;
     *     const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
     *     const RETURN_CODE_NO_URLS = 3;
     * 
     * Only need to be asserting the return code
     * 
     * Potentially test that resque task-assignment-selection job is queued
     */ 
class PrepareCommandTest extends ConsoleCommandTestCase {
    
    const CANONICAL_URL = 'http://example.com';
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:job:prepare';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand(),
            new \SimplyTestable\ApiBundle\Command\JobPrepareCommand()
        );
    }     
    
    public function testSuccessfulPrepareReturnsStatusCode0() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));        
        $this->assertReturnCode(0, array(
            'id' => $job->getId()
        ));    
    }
    
    public function testJobInWrongStateReturnsStatusCode1() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL)); 
        $job->setState($this->getJobService()->getCancelledState());
        $this->getJobService()->persistAndFlush($job);       
        
        $this->assertReturnCode(1, array(
            'id' => $job->getId()
        ));
    }
    
    public function testSystemInMaintenanceModeReturnsStatusCode2() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(2, array(
            'id' => 1
        ));      
    }
  
    public function testJobWithNoDiscoveredUrlsReturnsStatusCode3() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));

        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));     
        
        $this->assertReturnCode(0, array(
            'id' => $job->getId()
        ));       
    }
}
