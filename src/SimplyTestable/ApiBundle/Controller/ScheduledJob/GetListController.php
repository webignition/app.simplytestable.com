<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;

class GetListController extends ApiController
{
    /**
     * @return Response
     */
    public function listAction()
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $scheduledJobService->setUser($this->getUser());

        $list = $scheduledJobService->getList();

        $response = [];

        foreach ($list as $scheduledJob) {
            $response[] = [
                'id' => $scheduledJob->getId(),
                'jobconfiguration' => $scheduledJob->getJobConfiguration()->getLabel(),
                'schedule' => $scheduledJob->getCronJob()->getSchedule(),
                'schedule-modifier' => $scheduledJob->getCronModifier(),
                'isrecurring' => (int)$scheduledJob->getIsRecurring()
            ];
        }

        return $this->sendResponse($response);
    }
}
