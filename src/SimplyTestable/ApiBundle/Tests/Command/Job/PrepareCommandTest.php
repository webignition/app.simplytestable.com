<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

    /**
     * Required tests:
     *  - happy-path occurrences of the 5 return codes
     *     const RETURN_CODE_OK = 0;
     *     const RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE = 1;
     *     const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
     *     const RETURN_CODE_NO_URLS = 3;
     * 
     * Only need to be asserting the return code
     * 
     * Potentially test that resque task-assignment-selection job is queued
     */ 
class PrepareCommandTest extends BaseSimplyTestableTestCase {
    
    const CANONICAL_URL = 'http://example.com';
    
    public function testSuccessfulPrepareReturnsStatusCode0() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));      
    }
    
    public function testJobInWrongStateReturnsStatusCode1() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL)); 
        $job->setState($this->getJobService()->getCancelledState());
        $this->getJobService()->persistAndFlush($job);       
        
        $this->assertEquals(1, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));        
    }
    
    public function testSystemInMaintenanceModeReturnsStatusCode2() {
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));        
        $this->assertEquals(2, $this->runConsole('simplytestable:job:prepare', array(
            1 =>  true
        )));        
    }
  
    public function testJobWithNoDiscoveredUrlsReturnsStatusCode3() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));

        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));     
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));         
    }
}
