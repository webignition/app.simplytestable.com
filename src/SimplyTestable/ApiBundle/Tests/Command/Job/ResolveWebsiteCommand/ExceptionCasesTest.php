<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\ResolveWebsiteCommand;
 
class ExceptionCasesTest extends CommandTest {
    
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
    
    
    public function testHttpClientErrorPerformingResolution() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404'
        )));
        
        $this->job = $this->getJobService()->getById($this->createJobAndGetId(
            self::CANONICAL_URL,
            null,
            'single url',
            array('CSS Validation'),
            array(
                'CSS validation' => array(
                    'ignore-common-cdns' => 1,
                )
            )                
        ));
        
        $this->assertReturnCode(0, array(
            'id' => $this->job->getId()
        ));        
    }
}
