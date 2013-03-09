<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseTestCase;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;

class WorkerActivateVerifyCommandTest extends BaseTestCase {

    public function testSuccessfulActivateVerifyWorker() {        
        $this->resetSystemState();
        
        $workerHostname = 'test.worker.simplytestable.com';
        $activationRequestToken = 'token';
        
        $worker = $this->getWorkerService()->get($workerHostname);
        $this->assertEquals(1, $worker->getId());
        $this->assertEquals($workerHostname, $worker->getHostname());
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $response = $this->runConsole('simplytestable:worker:activate:verify', array(
            $worker->getId() =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(0, $response);
    }
    
    
    public function testFailedActivateVerifyWorker() {        
        $this->resetSystemState();
        
        $workerHostname = 'test.worker.simplytestable.com';
        $activationRequestToken = 'invalid-token';
        
        $worker = $this->getWorkerService()->get($workerHostname);
        $this->assertEquals(1, $worker->getId());
        $this->assertEquals($worker->getHostname(), $workerHostname);
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $response = $this->runConsole('simplytestable:worker:activate:verify', array(
            $worker->getId() =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        )); 
        
        $this->assertEquals(400, $response);
    }
    
    
    public function testActivateVerifyWhenWorkerIsInMaintenanceReadyOnlyMode() {
        $this->resetSystemState();
        
        $workerHostname = 'test.worker.simplytestable.com';
        $activationRequestToken = 'token';
        
        $worker = $this->getWorkerService()->get($workerHostname);
        $this->assertEquals(1, $worker->getId());
        $this->assertEquals($workerHostname, $worker->getHostname());
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $response = $this->runConsole('simplytestable:worker:activate:verify', array(
            $worker->getId() =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(503, $response);        
    }
    
    
    public function testActivateVerifyInIsInMaintenanceReadyOnlyModeReturnsCode1() {
        $this->resetSystemState();
        
        $workerHostname = 'test.worker.simplytestable.com';
        $activationRequestToken = 'token';
        
        $worker = $this->getWorkerService()->get($workerHostname);
        $this->assertEquals(1, $worker->getId());
        $this->assertEquals($workerHostname, $worker->getHostname());
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));
        $this->assertEquals(1, $this->runConsole('simplytestable:worker:activate:verify', array(
            $worker->getId() => true
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
    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }
    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\WorkerActivationRequestService
     */
    private function getWorkerActivationRequestService() {
        return $this->container->get('simplytestable.services.workeractivationrequestservice');
    }

}
