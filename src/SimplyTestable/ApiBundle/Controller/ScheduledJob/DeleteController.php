<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use Symfony\Component\HttpFoundation\Response;

class DeleteController extends ScheduledJobController
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
            return $this->sendNotFoundResponse();
        }

        $scheduledJobService->delete($scheduledJob);

        return $this->sendResponse();
    }
}
