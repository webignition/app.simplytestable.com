<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestsControllerTest extends BaseControllerJsonTestCase {

    public function testStartAction() {        
        $controllerName = 'SimplyTestable\ApiBundle\Controller\TestsController';
        $controller = $this->createController($controllerName);       
        /* @var $controller \SimplyTestable\ApiBundle\Controller\TestsController */       
        
        $response = $controller->startAction('http:\/\/example.com');
        $responseJsonObject = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
        
        $this->assertEquals('http:\/\/example.com', $responseJsonObject->site_root_url);
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
