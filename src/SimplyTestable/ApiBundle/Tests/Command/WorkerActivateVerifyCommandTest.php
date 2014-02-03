<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;

class WorkerActivateVerifyCommandTest extends ConsoleCommandTestCase {
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:worker:activate:verify';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(           
            new \SimplyTestable\ApiBundle\Command\WorkerActivateVerifyCommand()
        );
    }         

    public function testSuccessfulActivateVerifyWorker() {
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 200',
        )));  
        
        $activationRequestToken = 'token';        
        $worker = $this->createWorker();
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $this->assertReturnCode(0, array(
            'id' => $worker->getId()
        ));
    }
    
    
    public function testFailedActivateVerifyWorker() {        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 400',
        )));
        
        $activationRequestToken = 'invalid-token';        
        $worker = $this->createWorker();
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $this->assertReturnCode(400, array(
            'id' => $worker->getId()
        ));
    }
    
    
    public function testActivateVerifyWhenWorkerIsInMaintenanceReadyOnlyMode() {        
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 503',
        )));
        
        $activationRequestToken = 'token';        
        $worker = $this->createWorker();
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $this->assertReturnCode(503, array(
            'id' => $worker->getId()
        ));       
    }    
    
    public function testActivateVerifyInIsInMaintenanceReadyOnlyModeReturnsCode1() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(1, array(
            'id' => 1
        ));            
    }    
    
    
    public function testActivateVerifyRaisesHttpClientError() {
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
        )));
        
        $activationRequestToken = 'token';        
        $worker = $this->createWorker();
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $this->assertReturnCode(404, array(
            'id' => $worker->getId()
        ));            
    } 
    
    
    public function testActivateVerifyRaisesHttpServerError() {
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 500',
        )));
        
        $activationRequestToken = 'token';        
        $worker = $this->createWorker();
        
        $activationRequest = $this->createActivationRequest($worker, $activationRequestToken);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $this->assertReturnCode(500, array(
            'id' => $worker->getId()
        ));          
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
