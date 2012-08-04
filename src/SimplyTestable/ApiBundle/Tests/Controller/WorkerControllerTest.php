<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class WorkerControllerTest extends BaseControllerJsonTestCase {

    public function testActivateAction() { 
        //$this->container->
        
        $this->setupDatabase();
        
        $_POST = array(
            'hostname' => 'test.worker.simplytestable.com',
            'token' => 'valid-token'
        );        
        
        $controllerName = 'SimplyTestable\ApiBundle\Controller\WorkerController';
        $controller = $this->createController($controllerName, 'activateAction');
        /* @var $controller \SimplyTestable\ApiBundle\Controller\WorkerController */

        
        $response = $controller->activateAction();
        
//        $response1 = $controller->startAction('http://one.example.com');
//        $response1JsonObject = json_decode($response1->getContent());
//
//        $response2 = $controller->startAction('http://two.example.com');
//        $response2JsonObject = json_decode($response2->getContent());
//
//        $response3 = $controller->startAction('http://one.example.com');
//        $response3JsonObject = json_decode($response3->getContent());
//
//        $this->assertEquals(200, $response1->getStatusCode());
//        $this->assertEquals(200, $response2->getStatusCode());
//        $this->assertEquals(200, $response3->getStatusCode());
//
//        $this->assertEquals(1, $response1JsonObject->id);
//        $this->assertEquals('public', $response1JsonObject->user);
//        $this->assertEquals('http://one.example.com/', $response1JsonObject->website);        
//        $this->assertEquals('new', $response1JsonObject->state);
//        $this->assertEquals(0, count($response1JsonObject->tasks));
//        
//        $this->assertEquals(2, $response2JsonObject->id);
//        $this->assertEquals('public', $response2JsonObject->user);
//        $this->assertEquals('http://two.example.com/', $response2JsonObject->website);
//        $this->assertEquals('new', $response2JsonObject->state);
//        $this->assertEquals(0, count($response2JsonObject->tasks));
//        
//        $this->assertEquals(3, $response3JsonObject->id);
//        $this->assertEquals('public', $response3JsonObject->user);
//        $this->assertEquals('http://one.example.com/', $response3JsonObject->website);
//        $this->assertEquals('new', $response3JsonObject->state);
//        $this->assertEquals(0, count($response3JsonObject->tasks));
    }    

}
