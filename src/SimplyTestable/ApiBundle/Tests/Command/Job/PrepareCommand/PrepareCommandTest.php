<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand;

class PrepareCommandTest extends CommandTest {
    
    const CANONICAL_URL = 'http://example.com';

    public function testSuccessfulPrepareReturnsStatusCode0() {
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());
        $this->queuePrepareHttpFixturesForJob($job->getWebsite()->getCanonicalUrl());
        
        $this->assertReturnCode(0, array(
            'id' => $job->getId()
        ));    
    }
    
    public function testJobInWrongStateReturnsStatusCode1() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL));   
        
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
  
    public function testJobWithNoDiscoveredUrlsReturnsStatusCode0() {
        $job = $this->getJobService()->getById($this->createAndResolveDefaultJob());

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404'
        )));   
        
        $this->assertReturnCode(0, array(
            'id' => $job->getId()
        ));       
    }
}
