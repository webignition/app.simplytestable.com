<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiController extends Controller
{
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
     * @param array $headers
     * @return Response
     */
    public function sendFailureResponse($headers = [])
    {
        return Response::create('', 400, $headers);
    }

    /**
     * @return Response
     */
    public function sendNotFoundResponse()
    {
        return Response::create('', 404);
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
}
