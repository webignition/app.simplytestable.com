<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GetListController extends ApiController
{
    /**
     * @return JsonResponse|Response
     */
    public function listAction()
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $scheduledJobService->setUser($this->getUser());

        return new JsonResponse($scheduledJobService->getList());
    }
}
