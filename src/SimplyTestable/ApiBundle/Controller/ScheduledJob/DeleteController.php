<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteController extends Controller
{
    /**
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');

        $scheduledJob = $scheduledJobService->get($id);

        if (empty($scheduledJob)) {
            throw new NotFoundHttpException();
        }

        $scheduledJobService->delete($scheduledJob);

        return new Response();
    }
}
