<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StatusController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        $applicationStatusFactory = $this->container->get('simplytestable.services.applicationstatusfactory');

        return new JsonResponse($applicationStatusFactory->create());
    }
}
