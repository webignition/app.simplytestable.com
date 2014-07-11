<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class RoutingTest extends BaseSimplyTestableTestCase {

    public function testRouteExists() {        
        try {
            $this->getCurrentRequestUrl($this->getRouteParameters());
        } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException $routeNotFoundException) {
            $this->fail('Named route "' . $this->getRouteFromTestNamespace() . '" does not exist');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->fail($invalidArgumentException->getMessage());
        }
    }
    
    /**
     * @depends testRouteExists
     */
    public function testControllerExistsForRoute() {
        $this->assertArrayHasKey(
                self::ROUTER_MATCH_CONTROLLER_KEY,
                $this->getRouter()->match($this->getCurrentRequestUrl($this->getRouteParameters())),
                'No controller found for route [' . $this->getRouteFromTestNamespace() . ']'
        );
    }    
    
    
    /**
     * @depends testControllerExistsForRoute
     */
    public function testRouteHasExpectedController() {
        $this->assertEquals(
                $this->getExpectedRouteController(),
                $this->getRouteController(),
                'Incorrect controller found for route [' . $this->getRouteFromTestNamespace() . '].' . "\n" . 'Expected ' . $this->getExpectedRouteController() . ' got ' . $this->getRouteController() . '.' .  "\n" . 'Check routing.yml default controller for this route.'
        );

    }


    /**
     * @depends testRouteHasExpectedController
     */
    public function testRouteControllerExists() {
        $this->assertTrue(class_exists($this->getControllerNameFromRouter()));
    }


    /**
     * @depends testRouteControllerExists
     */
    public function testRouteControllerActionMethodExists() {
        $className = $this->getControllerNameFromRouter();

        $this->assertTrue(method_exists(new $className(), $this->getActionNameFromRouter()));
    }
    
    

    
}


