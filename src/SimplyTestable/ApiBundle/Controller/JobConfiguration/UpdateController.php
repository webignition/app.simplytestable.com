<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Adapter\Job\TaskConfiguration\RequestAdapter;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;

class UpdateController extends JobConfigurationController {

    public function updateAction($label) {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $this->getJobConfigurationService()->setUser($this->getUser());

        $jobConfiguration = $this->getJobConfigurationService()->get($label);
        if (is_null($jobConfiguration)) {
            return $this->sendNotFoundResponse();
        }

        try {
            $jobConfigurationValues = $this->getRequestJobConfigurationValues();

            $this->getJobConfigurationService()->update(
                $jobConfiguration,
                $jobConfigurationValues
            );

            return $this->redirect($this->generateUrl(
                'jobconfiguration_get_get',
                ['label' =>
                    ($jobConfigurationValues->hasEmptyLabel()) ? $jobConfiguration->getLabel(): $jobConfiguration->getLabel()
                ]
            ));
        } catch (JobConfigurationServiceException $jobConfigurationServiceException) {
            return $this->sendFailureResponse([
                'X-JobConfigurationCreate-Error' => json_encode([
                    'code' => $jobConfigurationServiceException->getCode(),
                    'message' => $jobConfigurationServiceException->getMessage()
                ])
            ]);
        }


    }


    /**
     * @return JobConfigurationValues
     */
    private function getRequestJobConfigurationValues() {
        $values = new JobConfigurationValues();
        $values->setLabel($this->getRequest()->request->get('label'));
        $values->setParameters($this->getRequest()->request->get('parameters'));
        $values->setTaskConfigurationCollection($this->getRequestTaskConfigurationCollection());
        $values->setWebsite($this->getRequestWebsite());
        $values->setType($this->getRequestJobType());

        return $values;
    }


    private function getRequestWebsite() {
        return $this->getWebsiteService()->fetch(
            trim($this->getRequest()->get('website'))
        );
    }


    /**
     * @return WebSiteService
     */
    private function getWebsiteService() {
        return $this->get('simplytestable.services.websiteservice');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobTypeService
     */
    private function getJobTypeService() {
        return $this->get('simplytestable.services.jobtypeservice');
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\TaskTypeService
     */
    private function getTaskTypeService() {
        return $this->get('simplytestable.services.tasktypeservice');
    }


    /**
     *
     * @return JobType
     */
    private function getRequestJobType() {
        if (!$this->getJobTypeService()->has($this->getRequestValue('type'))) {
            return $this->getJobTypeService()->getDefaultType();
        }

        return $this->getJobTypeService()->getByName($this->getRequestValue('type'));
    }


    /**
     * @return TaskConfigurationCollection
     */
    private function getRequestTaskConfigurationCollection() {
        $adapter = new RequestAdapter();
        $adapter->setRequest($this->getRequest());
        $adapter->setTaskTypeService($this->getTaskTypeService());

        return $adapter->getCollection();
    }

}
