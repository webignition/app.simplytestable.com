<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class WorkerControllerTest extends BaseControllerJsonTestCase {

    public function testActivateActionWithValidToken() {         
        $this->setupDatabase();
        
        $_POST = array(
            'hostname' => 'test.worker.simplytestable.com',
            'token' => 'valid-token'
        );        
        
        $controllerName = 'SimplyTestable\ApiBundle\Controller\WorkerController';
        $controller = $this->createController($controllerName, 'activateAction');
        /* @var $controller \SimplyTestable\ApiBundle\Controller\WorkerController */
        
        $this->container->get('simplytestable.services.httpClient')->getStoredResponseList()->setFixturesPath(
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'                
        );
        
        $response = $controller->activateAction();
        $responseObject = json_decode($response->getContent());
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("ok", $responseObject);
    }    

    
    public function testActivateActionWithInValidToken() {         
//        $this->setupDatabase();
//        
//        $_POST = array(
//            'hostname' => 'test.worker.simplytestable.com',
//            'token' => 'invalid-token'
//        );        
//        
//        $controllerName = 'SimplyTestable\ApiBundle\Controller\WorkerController';
//        $controller = $this->createController($controllerName, 'activateAction');
//        /* @var $controller \SimplyTestable\ApiBundle\Controller\WorkerController */
//        
//        $this->container->get('simplytestable.services.httpClient')->getStoredResponseList()->setFixturesPath(
//            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'                
//        );
//        
//        $response = $controller->activateAction();
//        $responseObject = json_decode($response->getContent());
//        
//        $this->assertEquals(200, $response->getStatusCode());
//        $this->assertEquals("failure", $responseObject);
    }     
}
