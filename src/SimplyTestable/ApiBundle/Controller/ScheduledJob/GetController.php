<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetController extends Controller
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
