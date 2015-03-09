<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

class GetController extends JobConfigurationController {

    public function getAction($label) {
        $label = trim($label);

        $this->getJobConfigurationService()->setUser($this->getUser());

        $jobConfiguration = $this->getJobConfigurationService()->get($label);
        if (is_null($jobConfiguration)) {
            return $this->sendNotFoundResponse();
        }

        return $this->sendResponse(
            $this->getJobConfigurationService()->get($label)
        );
    }

}
