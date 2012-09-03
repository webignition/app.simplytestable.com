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
     * @var RequestService
     */
    private $requestService;
    
    
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
    protected function sendResponse($object, $statusCode = 200) {        
        $output = $this->getSerializer()->serialize($object, 'json');   
        
        $response = new Response($output); 
        $response->setStatusCode($statusCode);
        
        return $response;
    }
    
    
    /**
     *
     * @return RequestService
     */
    protected function getRequestService() {
        if (is_null($this->requestService)) {
            $this->requestService = $this->container->get('simplytestable.services.requestservice');
            $this->requestService->setRequest($this->get('request'));
        }
        
        return $this->requestService;
    }
    
    
    /**
     *
     * @return Request
     */
    public function getRequest() {
        return $this->getRequestService()->getRequest();
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
                $this->arguments = $this->getRequestService()->getRequest()->request;
            } else {
                $this->arguments = $this->getRequestService()->getRequest()->query;
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
     * @return \JMS\SerializerBundle\Serializer\Serializer
     */
    protected function getSerializer() {
        return $this->container->get('serializer');
    }
}
