<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class RoutingTest extends BaseSimplyTestableTestCase {
    
    const ROUTER_MATCH_CONTROLLER_KEY = '_controller';
    
    abstract protected function getRouteParameters();
    
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
    
    
    /**
     * 
     * @return string
     */
    private function getControllerNameFromRouter() {
        return explode('::', $this->getRouteController())[0];
    }
    
    
    /**
     * 
     * @return string
     */
    private function getActionNameFromRouter() {
        return explode('::', $this->getRouteController())[1];
    }
    
    
    
    private function getRouteController() {
        return $this->getRouter()->match($this->getCurrentRequestUrl($this->getRouteParameters()))[self::ROUTER_MATCH_CONTROLLER_KEY];
    }
    
    
    protected function getCurrentRequestUrl() {
        return $this->getCurrentController()->generateUrl($this->getRouteFromTestNamespace(), $this->getRouteParameters());
    }    
    
    protected function getCurrentController($postData = null, $queryData = null) {                
        $postData = (is_array($postData)) ? $postData : array();
        $queryData = (is_array($queryData)) ? $queryData : array();
        
        return $this->getController(
            $this->getControllerNameFromTestNamespace(),
            $this->getActionNameFromTestNamespace(),
            $postData,
            $queryData
        );
    }    
    
    
    /**
     * Get route name for current test
     * 
     * Is extracted from the class namespace as follows:
     * \Acme\FooBundle\Tests\Controller\Foo => 'foo'
     * \Acme\FooBundle\Tests\Controller\FooBar => 'foo_bar'
     * \Acme\FooBundle\Tests\Controller\FooBar\Bar => 'foobar_bar'
     * 
     * @return string
     */
    private function getRouteFromTestNamespace() {   
        return strtolower(implode('_', $this->getControllerRelatedNamespaceParts()) . '_' . str_replace('Action', '', $this->getActionNameFromTestNamespace())); 
    }
    
    
    /**
     * 
     * @return string
     */
    private function getExpectedRouteController() {
        return $this->getControllerNameFromTestNamespace() . '::' . $this->getActionNameFromTestNamespace();
    }
    
    
    /**
     * Get controller name from current test namespace
     * 
     * @return string
     */
    private function getControllerNameFromTestNamespace() {        
        return implode('\\', $this->getControllerNamespaceParts()) . 'Controller';
    }
    
    
    
    /**
     * Get controller action from current test namespace
     * 
     * @return string
     */
    private function getActionNameFromTestNamespace() {
        foreach ($this->getNamespaceParts() as $part) {
            if (preg_match('/.+Action$/', $part)) {
                return lcfirst($part);
            }
        }
    }
    
    
    /**
     * 
     * @return string[]
     */
    private function getControllerNamespaceParts() {
        $relevantParts = array();
        
        foreach ($this->getNamespaceParts() as $part) {
            if (preg_match('/.+Action$/', $part)) {
                return $relevantParts;
            }
            
            if ($part != 'Tests') {
                $relevantParts[] = $part;
            }
        }
        
        return $relevantParts;       
    }
    
    
    /**
     * 
     * @return string[]
     */
    private function getControllerRelatedNamespaceParts() {
        $parts = $this->getControllerNamespaceParts();

        foreach ($parts as $index => $part) {
            if ($part === 'Controller') {
                return array_slice($parts, $index + 1);
            }
        }
        
        return $parts;       
    }
    
    
    /**
     * 
     * @return string[]
     */
    private function getNamespaceParts() {
        $parts = explode('\\', get_class($this));
        array_pop($parts);       
        
        return $parts;
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    private function getRouter() {
        return $this->client->getContainer()->get('router');        
    }
    
}


