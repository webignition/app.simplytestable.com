<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

class GetListController extends JobConfigurationController {

    public function listAction() {
        $this->getJobConfigurationService()->setUser($this->getUser());
        return $this->sendResponse(
            $this->getJobConfigurationService()->getList()
        );
    }

}
