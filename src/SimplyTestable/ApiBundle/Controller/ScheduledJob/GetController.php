<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

class GetController extends ScheduledJobController {

    public function getAction($id) {
        $this->getScheduledJobService()->setUser($this->getUser());

        $scheduledJob = $this->getScheduledJobService()->get($id);

        if (is_null($scheduledJob)) {
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
