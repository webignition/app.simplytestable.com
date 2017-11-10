<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Services\ApplicationStatusFactory;
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
        $applicationStatusFactory = $this->container->get(ApplicationStatusFactory::class);

        return new JsonResponse($applicationStatusFactory->create());
    }
}
