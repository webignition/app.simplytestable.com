<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GetListController extends Controller
{
    /**
     * @return JsonResponse|Response
     */
    public function listAction()
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');

        return new JsonResponse($scheduledJobService->getList());
    }
}
