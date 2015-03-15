<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

class GetListController extends ScheduledJobController {

    public function listAction() {
        $this->getScheduledJobService()->setUser($this->getUser());

        $list = $this->getScheduledJobService()->getList();

        $response = [];

        foreach ($list as $scheduledJob) {
            $response[] = [
                'id' => $scheduledJob->getId(),
                'jobconfiguration' => $scheduledJob->getJobConfiguration()->getLabel(),
                'schedule' => $scheduledJob->getCronJob()->getSchedule(),
                'isrecurring' => (int)$scheduledJob->getIsRecurring()
            ];
        }

        return $this->sendResponse($response);
    }

}
