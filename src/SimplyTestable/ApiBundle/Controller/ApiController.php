<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Services\RequestService;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class ApiController extends Controller
{      
   
    /**
     *
     * @var ParameterBag 
     */
    private $arguments;
    
    /**
     *
     * @var array
     */
    private $inputDefinitions = array();
    
    
    /**
     *
     * @var array
     */
    private $requestTypes = array();
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\ApplicationStateServic
     */
    private $applicationStateService;
    
    
    /**
     * Set collection of InputDefinition objects
     * key is controller method name
     * value is InputDefinition
     * 
     * @param array $inputDefinition Collection of InputDefintions
     */
    protected function setInputDefinitions($inputDefinitions) {
        $this->inputDefinitions = $inputDefinitions;
    }
    
    /**
     *
     * @param mixed $object
     * @param int statusCode
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    protected function sendResponse($object = null, $statusCode = 200) {
        $output = (is_null($object)) ? '' : $this->getSerializer()->serialize($object, 'json');   
        
        $response = new Response($output); 
        $response->setStatusCode($statusCode);
        
        return $response;
    }    
    
    /**
     *
     * @param int $requestType
     * @return \SimplyTestable\ApiBundle\Controller\ApiController 
     */
    protected function setRequestTypes($requestTypes) {        
        $this->requestTypes = $requestTypes;
        return $this;
    }
    
    
    /**
     * @param string $methodName
     * @return ParameterBag
     */
    public function getArguments($methodName) {        
        if (is_null($this->arguments)) {                                    
            if ($this->getRequestType($methodName) === HTTP_METH_POST) {                
                $this->arguments = $this->get('request')->request;
            } else {
                $this->arguments = $this->get('request')->query;
            }
        }        
        
        return $this->arguments;
    }
    
    
    /**
     * @param string $methodName
     * @return InputDefinition
     */
    public function getInputDefinition($methodName) {
        if (!isset($this->inputDefinitions[$methodName])) {
            return new InputDefinition();
        }
        
        return $this->inputDefinitions[$methodName];
    }
    
    
    /**
     * 
     * @param string $methodName
     * @return int
     */
    private function getRequestType($methodName) {
        if (!is_array($this->requestTypes)) {
            return HTTP_METH_GET;
        }
        
        if (!isset($this->requestTypes[$methodName])) {
            return HTTP_METH_GET;
        }
        
        return $this->requestTypes[$methodName];
    }
    
    
    /**
     *
     * @param string $methodName
     * @return Response
     */
    public function sendMissingRequiredArgumentResponse($methodName) {
        return $this->sendResponse($this->getInputDefinition($methodName));        
    }
    
    
    /**
     *
     * @return Response
     */
    public function sendSuccessResponse() {
        return $this->sendResponse('');
    }
    
    
    /**
     * 
     * @return Response
     */
    public function sendFailureResponse() {
        return $this->sendResponse('', 400);
    }
    
    
    /**
     * 
     * @return Response
     */
    public function sendServiceUnavailableResponse() {
        return $this->sendResponse(null, 503);
    }     
    
    
    /**
     *
     * @return \JMS\SerializerBundle\Serializer\Serializer
     */
    protected function getSerializer() {
        return $this->container->get('serializer');
    }
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\User
     */
    public function getUser() {
        return parent::getUser();
    }
    
    
    protected function getRequestValue($key, $httpMethod = null) {
        $availableHttpMethods = array(
            HTTP_METH_GET,
            HTTP_METH_POST
        );
        
        $defaultHttpMethod = HTTP_METH_GET;
        $requestedHttpMethods = array();
        
        if (is_null($httpMethod)) {
            $requestedHttpMethods = $availableHttpMethods;
        } else {
            if (in_array($httpMethod, $availableHttpMethods)) {
                $requestedHttpMethods[] = $httpMethod;
            } else {
                $requestedHttpMethods[] = $defaultHttpMethod;
            }
        }
        
        foreach ($requestedHttpMethods as $requestedHttpMethod) {
            $requestValues = $this->getRequestValues($requestedHttpMethod);
            if ($requestValues->has($key)) {
                return $requestValues->get($key);
            }
        }
        
        return null;       
    }
    
    
    /**
     *
     * @param int $httpMethod
     * @return type 
     */
    protected function getRequestValues($httpMethod = HTTP_METH_GET) {
        return ($httpMethod == HTTP_METH_POST) ? $this->container->get('request')->request : $this->container->get('request')->query;
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\ApplicationStateService
     */
    protected function getApplicationStateService() {
        if (is_null($this->applicationStateService)) {
            $this->applicationStateService = $this->get('simplytestable.services.applicationStateService');
            $this->applicationStateService->setStateResourcePath($this->getStateResourcePath());
        }
        
        return $this->applicationStateService;
    }
    

    /**
     * 
     * @return string
     */
    private function getStateResourcePath() {
        return $this->container->get('kernel')->locateResource('@SimplyTestableApiBundle/Resources/config') . '/state-' . $this->get('kernel')->getEnvironment();
    }
}
