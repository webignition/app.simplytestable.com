<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use Symfony\Component\HttpFoundation\Response;

class GetController extends ScheduledJobController
{
    /**
     * @param int $id
     *
     * @return Response
     */
    public function getAction($id)
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $scheduledJobService->setUser($this->getUser());

        $scheduledJob = $scheduledJobService->get($id);

        if (empty($scheduledJob)) {
            return $this->sendNotFoundResponse();
        }

        return $this->sendResponse([
            'id' => $scheduledJob->getId(),
            'jobconfiguration' => $scheduledJob->getJobConfiguration()->getLabel(),
            'schedule' => $scheduledJob->getCronJob()->getSchedule(),
            'schedule-modifier' => $scheduledJob->getCronModifier(),
            'isrecurring' => (int)$scheduledJob->getIsRecurring(),
        ]);
    }
}
