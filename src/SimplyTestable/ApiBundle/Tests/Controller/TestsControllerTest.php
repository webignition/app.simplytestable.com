<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestsControllerTest extends BaseControllerJsonTestCase {

    public function testStartAction() {        
        $this->setupDatabase();
        
        $controllerName = 'SimplyTestable\ApiBundle\Controller\TestsController';
        $controller = $this->createController($controllerName);       
        /* @var $controller \SimplyTestable\ApiBundle\Controller\TestsController */        
        
        $response1 = $controller->startAction('http:\/\/one.example.com');
        $response1JsonObject = json_decode($response1->getContent());
        
        $response2 = $controller->startAction('http:\/\/two.example.com');
        $response2JsonObject = json_decode($response2->getContent());
        
        $response3 = $controller->startAction('http:\/\/one.example.com');
        $response3JsonObject = json_decode($response3->getContent());        

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertEquals(200, $response3->getStatusCode());
        $this->assertEquals(1, $response1JsonObject->job_id);
        $this->assertEquals(2, $response2JsonObject->job_id);
        $this->assertEquals(1, $response3JsonObject->job_id);
    }
    
    public function testStatusAction() {
        $controllerName = 'SimplyTestable\ApiBundle\Controller\TestsController';
        $controller = $this->createController($controllerName);
        /* @var $controller \SimplyTestable\ApiBundle\Controller\TestsController */
        
        $response = $controller->statusAction('http:\/\/example.com', 1);
        $responseJsonObject = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals('http:\/\/example.com', $responseJsonObject->site_root_url);
        $this->assertEquals(1, $responseJsonObject->test_id);
    }    
    
    public function testResultsAction() {
        $controllerName = 'SimplyTestable\ApiBundle\Controller\TestsController';
        $controller = $this->createController($controllerName);
        /* @var $controller \SimplyTestable\ApiBundle\Controller\TestsController */
        
        $response = $controller->resultsAction('http:\/\/example.com', 1);
        $responseJsonObject = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
        
        $this->assertEquals('http:\/\/example.com', $responseJsonObject->site_root_url);
        $this->assertEquals(1, $responseJsonObject->test_id);
    }    

}
