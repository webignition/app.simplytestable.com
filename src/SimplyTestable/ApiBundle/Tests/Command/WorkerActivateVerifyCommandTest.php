<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseTestCase;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;

class WorkerActivateVerifyCommandTest extends \SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }         

    public function testSuccessfulActivateVerifyWorker() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $workerHostname = 'test.worker.simplytestable.com';
        $activationRequestToken = 'token';
        
        $worker = $this->createWorker($workerHostname);
        
        $this->assertInternalType('integer', $worker->getId());
        $this->assertGreaterThan(0, $worker->getId());
        $this->assertEquals($workerHostname, $worker->getHostname());
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $this->assertEquals(0, $this->runConsole('simplytestable:worker:activate:verify', array(
            $worker->getId() =>  true
        )));
    }
    
    
    public function testFailedActivateVerifyWorker() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $workerHostname = 'test.worker.simplytestable.com';
        $activationRequestToken = 'invalid-token';
        
        $worker = $this->createWorker($workerHostname);
        $this->assertInternalType('integer', $worker->getId());
        $this->assertGreaterThan(0, $worker->getId());
        $this->assertEquals($workerHostname, $worker->getHostname());
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $this->assertEquals(400, $this->runConsole('simplytestable:worker:activate:verify', array(
            $worker->getId() =>  true
        )));
    }
    
    
    public function testActivateVerifyWhenWorkerIsInMaintenanceReadyOnlyMode() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $workerHostname = 'test.worker.simplytestable.com';
        $activationRequestToken = 'token';
        
        $worker = $this->createWorker($workerHostname);
        $this->assertInternalType('integer', $worker->getId());
        $this->assertGreaterThan(0, $worker->getId());
        $this->assertEquals($workerHostname, $worker->getHostname());
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $this->assertEquals(503, $this->runConsole('simplytestable:worker:activate:verify', array(
            $worker->getId() =>  true

        )));        
    }    
    
    public function testActivateVerifyInIsInMaintenanceReadyOnlyModeReturnsCode1() {
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));
        $this->assertEquals(1, $this->runConsole('simplytestable:worker:activate:verify', array(
            1 => true
        )));        
    }    
    
    
    public function testActivateVerifyRaisesHttpClientError() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $workerHostname = 'test.worker.simplytestable.com';
        $activationRequestToken = 'token';
        
        $worker = $this->createWorker($workerHostname);
        
        $this->assertInternalType('integer', $worker->getId());
        $this->assertGreaterThan(0, $worker->getId());
        $this->assertEquals($workerHostname, $worker->getHostname());
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $this->assertEquals(404, $this->runConsole('simplytestable:worker:activate:verify', array(
            $worker->getId() =>  true
        )));      
    } 
    
    
    public function testActivateVerifyRaisesHttpServerError() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $workerHostname = 'test.worker.simplytestable.com';
        $activationRequestToken = 'token';
        
        $worker = $this->createWorker($workerHostname);
        
        $this->assertInternalType('integer', $worker->getId());
        $this->assertGreaterThan(0, $worker->getId());
        $this->assertEquals($workerHostname, $worker->getHostname());
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $this->assertEquals(500, $this->runConsole('simplytestable:worker:activate:verify', array(
            $worker->getId() =>  true
        )));       
    } 
    
    
    /**
     *
     * @param Worker $worker
     * @param string $token
     * @return \SimplyTestable\ApiBundle\Entity\WorkerActivationRequest
     */
    private function createActivationRequest(Worker $worker, $token) {
        return $this->getWorkerActivationRequestService()->create($worker, $token);
    }

}
