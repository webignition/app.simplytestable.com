<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

class DeleteController extends JobConfigurationController {

    public function deleteAction($label) {
        $label = trim($label);

        $this->getJobConfigurationService()->setUser($this->getUser());

        $jobConfiguration = $this->getJobConfigurationService()->get($label);
        if (is_null($jobConfiguration)) {
            return $this->sendNotFoundResponse();
        }

        $this->getJobConfigurationService()->delete($label);

        return $this->sendResponse();
    }

}
