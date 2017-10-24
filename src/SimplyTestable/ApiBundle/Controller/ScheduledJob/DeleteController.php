<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteController extends ApiController
{
    /**
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $scheduledJobService->setUser($this->getUser());

        $scheduledJob = $scheduledJobService->get($id);

        if (empty($scheduledJob)) {
            throw new NotFoundHttpException();
        }

        $scheduledJobService->delete($scheduledJob);

        return new Response();
    }
}
