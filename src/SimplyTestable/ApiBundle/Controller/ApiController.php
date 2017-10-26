<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiController extends Controller
{
    /**
     * @param mixed $object
     * @param int $statusCode
     *
     * @return Response
     */
    protected function sendResponse($object = null, $statusCode = 200)
    {
        $serializer = $this->container->get('serializer');

        $output = (is_null($object)) ? '' : $serializer->serialize($object, 'json');

        $response = new Response($output);
        $response->setStatusCode($statusCode);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
