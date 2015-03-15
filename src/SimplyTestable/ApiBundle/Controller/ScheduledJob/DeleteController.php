<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

class DeleteController extends ScheduledJobController {

    public function deleteAction($id) {
        $this->getScheduledJobService()->setUser($this->getUser());

        $scheduledJob = $this->getScheduledJobService()->get($id);

        if (is_null($scheduledJob)) {
            return $this->sendNotFoundResponse();
        }

        $this->getScheduledJobService()->delete($scheduledJob);

        return $this->sendResponse();
    }

}
