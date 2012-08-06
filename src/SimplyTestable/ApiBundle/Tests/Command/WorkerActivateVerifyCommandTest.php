<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseTestCase;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;

class WorkerActivateVerifyCommandTest extends BaseTestCase {    
    
    const TESTS_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\TestsController';
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Controller\TestsController
     */
    private $testsController = null;

    public function testSuccessfulActivateVerifyWorker() {        
        $this->setupDatabase();
        
        $worker = $this->getWorkerService()->get('test.worker.simplytestable.com');
        $this->assertEquals(1, $worker->getId());
        $this->assertEquals($worker->getHostname(), 'test.worker.simplytestable.com');
        
        $activationRequest = $this->createActivationRequest($worker);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $response = $this->runConsole('simplytestable:worker:activate:verify', array(
            $worker->getId() =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        )); 
        
        $this->assertEquals(0, $response);
    }
    
    
    public function testFailedActivateVerifyWorker() {        
        $this->setupDatabase();
        
        $worker = $this->getWorkerService()->get('test.worker.simplytestable.com');
        $this->assertEquals(1, $worker->getId());
        $this->assertEquals($worker->getHostname(), 'test.worker.simplytestable.com');
        
        $activationRequest = $this->createActivationRequest($worker);
        
        $this->assertTrue($activationRequest->getWorker()->equals($worker));
        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerActivationRequestService()->getStartingState()));
        
        $response = $this->runConsole('simplytestable:worker:activate:verify', array(
            $worker->getId() =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        )); 
        
        $this->assertEquals(1, $response);
    }    
    
    
    /**
     *
     * @param Worker $worker
     * @return \SimplyTestable\ApiBundle\Entity\WorkerActivationRequest
     */
    private function createActivationRequest(Worker $worker) {
        return $this->getWorkerActivationRequestService()->get($worker);
    }
    
    
    
    /**
     *
     * @param string $canonicalUrl
     * @return Job
     */
    private function createJob($canonicalUrl) {
        return $this->getWorkerController('startAction')->startAction($canonicalUrl);
    }
    
    
    /**
     *
     * @param string $canonicalUrl
     * @param int $id
     * @return Job
     */
    private function fetchJob($canonicalUrl, $id) {        
        return $this->getWorkerController('statusAction')->statusAction($canonicalUrl, $id);    
    }
    
    
    /**
     *
     * @param string $methodName
     * @return SimplyTestable\ApiBundle\Controller\TestsController
     */
    private function getWorkerController($methodName) {
        if (is_null($this->testsController)) {
            $this->testsController = $this->createController(self::TESTS_CONTROLLER_NAME, $methodName);
        }        
        
        return $this->testsController;
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
