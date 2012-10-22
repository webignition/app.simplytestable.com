<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
use SimplyTestable\ApiBundle\Services\WorkerService;

class WorkerControllerTest extends BaseControllerJsonTestCase {
    
    const WORKER_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\WorkerController';

    public function testActivateAction() {
        $this->setupDatabase();
        
        $hostname = 'test.worker.simplytestable.com';
        $token = 'valid-token';
        
        $_POST = array(
            'hostname' => $hostname,
            'token' => $token
        );        
        
        /* @var $controller \SimplyTestable\ApiBundle\Controller\WorkerController */
        $controller = $this->createController(self::WORKER_CONTROLLER_NAME, 'activateAction');        
        
        $this->container->get('simplytestable.services.httpClient')->getStoredResponseList()->setFixturesPath(
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'                
        );
        
        $response = $controller->activateAction();       
        $this->assertEquals(200, $response->getStatusCode());              
    }
    
    public function testActivateActionWithMissingHostname() {
        $this->setupDatabase();
        
        $token = 'valid-token';
        
        $_POST = array(
            'token' => $token
        );        
        
        try {
            $this->createController(self::WORKER_CONTROLLER_NAME, 'activateAction');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            return $this->assertEquals(400, $httpException->getStatusCode());
        } 
        
        $this->fail('WorkerController::activateAction() didn\'t throw a 400 HttpException for a missing hostname');  
    }
    
    public function testActivateActionWithMissingToken() {
        $this->setupDatabase();
        
        $hostname = 'test.worker.simplytestable.com';
        
        $_POST = array(
            'hostname' => $hostname
        );        
        
        try {
            $this->createController(self::WORKER_CONTROLLER_NAME, 'activateAction');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            return $this->assertEquals(400, $httpException->getStatusCode());
        } 
        
        $this->fail('WorkerController::activateAction() didn\'t throw a 400 HttpException for a missing hostname');  
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\WorkerActivationRequestService 
     */
    private function getWorkerRequestActivationService() {
        return $this->container->get('simplytestable.services.workeractivationrequestservice');
    }
    
}


