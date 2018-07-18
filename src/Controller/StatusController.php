<?php

namespace App\Controller;

use App\Services\ApplicationStatusFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StatusController extends AbstractController
{
    /**
     * @param ApplicationStatusFactory $applicationStatusFactory
     *
     * @return Response
     */
    public function indexAction(ApplicationStatusFactory $applicationStatusFactory)
    {
        return new JsonResponse($applicationStatusFactory->create());
    }
}
