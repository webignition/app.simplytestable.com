<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetController extends ApiController
{
    /**
     * @param int $id
     *
     * @return JsonResponse|Response
     */
    public function getAction($id)
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $scheduledJobService->setUser($this->getUser());

        $scheduledJob = $scheduledJobService->get($id);

        if (empty($scheduledJob)) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($scheduledJob);
    }
}
