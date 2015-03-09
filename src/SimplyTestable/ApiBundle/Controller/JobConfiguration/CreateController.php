<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Adapter\Job\TaskConfiguration\RequestAdapter;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use Guzzle\Http\Message\Request as GuzzleRequest;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;

class CreateController extends JobConfigurationController {

    public function __construct() {
        $this->setInputDefinitions(array(
            'createAction' => new InputDefinition(array(
                    new InputArgument('label', InputArgument::REQUIRED, 'Identifying unique label for configuration'),
                    new InputArgument('website', InputArgument::REQUIRED, 'Site or page URL to be tested'),
                    new InputArgument('type', InputArgument::REQUIRED, 'Job type'),
                    new InputArgument('task-configuration', InputArgument::REQUIRED, 'Task config; array of [task type name] => [opts]'),
                    new InputArgument('parameters', InputArgument::OPTIONAL, 'Job parameters string (json)')
                ))
        ));

        $this->setRequestTypes(array(
            'createAction' => GuzzleRequest::POST
        ));
    }

    public function createAction() {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($this->getUserService()->isSpecialUser($this->getUser())) {
            return $this->sendFailureResponse([
                'X-JobConfigurationCreate-Error' => json_encode([
                    'code' => 99,
                    'message' => 'Special users cannot create job configurations'
                ])
            ]);
        }

        $this->getJobConfigurationService()->setUser($this->getUser());

        try {
            $jobConfiguration = $this->getJobConfigurationService()->create(
                $this->getRequestWebsite(),
                $this->getRequestJobType(),
                $this->getRequestTaskConfigurationCollection(),
                $this->getRequest()->get('label'),
                $this->getRequest()->get('parameters')
            );

            return $this->redirect($this->generateUrl(
                'jobconfiguration_get_get',
                ['label' => $jobConfiguration->getLabel()]
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
