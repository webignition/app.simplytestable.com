<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class WorkerControllerTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }     

    public function testActivateAction() {
        $this->removeAllWorkers();     
      
        $this->assertEquals(200, $this->getWorkerController('activateAction', array(
            'hostname' => 'test.worker.simplytestable.com',
            'token' => 'valid-token'
        ))->activateAction()->getStatusCode());              
    }
    
    public function testActivateActionWithMissingHostname() {
        $this->removeAllWorkers();   
        
        try {
            $this->getWorkerController('activateAction', array(
                'token' => 'valid-token'
            ))->activateAction()->getStatusCode(); 
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            return $this->assertEquals(400, $httpException->getStatusCode());
        } 
        
        $this->fail('WorkerController::activateAction() didn\'t throw a 400 HttpException for a missing hostname');  
    }
    
    public function testActivateActionWithMissingToken() {
        $this->removeAllWorkers();    
        
        try {
            $this->getWorkerController('activateAction', array(
                'hostname' => 'test.worker.simplytestable.com'
            ))->activateAction()->getStatusCode(); 
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            return $this->assertEquals(400, $httpException->getStatusCode());
        } 
        
        $this->fail('WorkerController::activateAction() didn\'t throw a 400 HttpException for a missing token');  
    }
    
    
    public function testActivateActionInMaintenanceReadOnlyModeReturns503() {    
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));          
        
        $this->assertEquals(503, $this->getWorkerController('activateAction', array(
            'hostname' => '',
            'token' => ''
        ))->activateAction()->getStatusCode());        
    }
    
}