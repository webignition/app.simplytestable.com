<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiController extends Controller
{
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserService
     */
    protected function getUserService() {
        return $this->get('simplytestable.services.userservice');
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
}
