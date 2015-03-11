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
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use Symfony\Component\HttpFoundation\Request;

class CreateController extends JobConfigurationController {

    /**
     * @var Request
     */
    private $request;

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

    public function createAction(Request $request) {
        $this->request = $request;

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
            $jobConfigurationValues = new JobConfigurationValues();
            $jobConfigurationValues->setWebsite($this->getRequestWebsite());
            $jobConfigurationValues->setType($this->getRequestJobType());
            $jobConfigurationValues->setTaskConfigurationCollection($this->getRequestTaskConfigurationCollection());
            $jobConfigurationValues->setLabel($this->request->get('label'));
            $jobConfigurationValues->setParameters($this->request->get('parameters'));

            $jobConfiguration = $this->getJobConfigurationService()->create($jobConfigurationValues);

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
            trim($this->request->get('website'))
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
        $adapter->setRequest($this->request);
        $adapter->setTaskTypeService($this->getTaskTypeService());

        return $adapter->getCollection();
    }

}
