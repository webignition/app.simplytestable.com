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
     * @return \SimplyTestable\ApiBundle\Services\UserService
     */
    protected function getUserService() {
        return $this->get('simplytestable.services.userservice');
    }


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
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @param string $methodName
     * @return ParameterBag
     */
    public function getArguments($methodName) {
        if (is_null($this->arguments)) {
            if ($this->getRequestType($methodName) === \Guzzle\Http\Message\Request::POST) {
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
            return \Guzzle\Http\Message\Request::GET;
        }

        if (!isset($this->requestTypes[$methodName])) {
            return \Guzzle\Http\Message\Request::GET;
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
    public function sendFailureResponse($headers = null) {
        if (is_array($headers)) {
            return Response::create('', 400, $headers);
        }

        return $this->sendResponse('', 400);
    }

    /**
     *
     * @return Response
     */
    public function sendForbiddenResponse() {
        return $this->sendResponse('', 403);
    }


    /**
     *
     * @return Response
     */
    public function sendGoneResponse() {
        return $this->sendResponse('', 410);
    }

    /**
     *
     * @return Response
     */
    public function sendNotFoundResponse() {
        return $this->sendResponse('', 404);
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
        if (!is_null($this->getUserService()->getUser())) {
            return $this->getUserService()->getUser();
        }

        return parent::getUser();
    }


    protected function getRequestValue($key, $httpMethod = null) {
        $availableHttpMethods = array(
            \Guzzle\Http\Message\Request::GET,
            \Guzzle\Http\Message\Request::POST
        );

        $defaultHttpMethod = \Guzzle\Http\Message\Request::GET;
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
    protected function getRequestValues($httpMethod = \Guzzle\Http\Message\Request::GET) {
        return ($httpMethod == \Guzzle\Http\Message\Request::POST) ? $this->container->get('request')->request : $this->container->get('request')->query;
    }
}
