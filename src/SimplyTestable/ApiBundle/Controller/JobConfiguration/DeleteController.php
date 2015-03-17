<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;

class DeleteController extends JobConfigurationController {

    public function deleteAction($label) {
        $label = trim($label);

        $this->getJobConfigurationService()->setUser($this->getUser());

        $jobConfiguration = $this->getJobConfigurationService()->get($label);
        if (is_null($jobConfiguration)) {
            return $this->sendNotFoundResponse();
        }

        if (is_null($this->getScheduledJobService()->getEntityRepository()->findOneBy(['jobConfiguration' => $jobConfiguration]))) {
            $this->getJobConfigurationService()->delete($label);
            return $this->sendResponse();
        }

        return $this->sendFailureResponse([
            'X-JobConfigurationDelete-Error' => json_encode([
                'code' => 1,
                'message' => 'Job configuration is in use by a scheduled job'
            ])
        ]);
    }


    /**
     * @return ScheduledJobService
     */
    private function getScheduledJobService() {
        return $this->container->get('simplytestable.services.scheduledjob.service');
    }

}
