<?php

namespace SimplyTestable\ApiBundle\Adapter\Job\Configuration\Start;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestAdapter {

    /**
     * @var Request
     */
    private $request;


    /**
     * @var WebSiteService
     */
    private $websiteService;


    /**
     * @var JobTypeService
     */
    private $jobTypeService;


    /**
     * @var TaskTypeService
     */
    private $taskTypeService;


    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;


    public function __construct(
        Request $request,
        WebSiteService $webSiteService,
        JobTypeService $jobTypeService,
        TaskTypeService $taskTypeService
    ) {
        $this->request = $request;
        $this->jobConfiguration = null;
        $this->websiteService = $webSiteService;
        $this->jobTypeService = $jobTypeService;
        $this->taskTypeService = $taskTypeService;
    }


    /**
     * @return JobConfiguration
     */
    public function getJobConfiguration() {
        if (is_null($this->jobConfiguration)) {
            $this->build();
        }

        return $this->jobConfiguration;
    }


    private function build() {
        $this->jobConfiguration = new JobConfiguration();
        $this->jobConfiguration->setWebsite($this->getRequestWebsite());
        $this->jobConfiguration->setType($this->getRequestJobType());

        foreach ($this->getTaskConfigurationCollection()->get() as $taskConfiguration) {
            $this->jobConfiguration->addTaskConfiguration($taskConfiguration);
        }

        if ($this->hasRequestParameters()) {
            $this->jobConfiguration->setParameters($this->getRequestParameters());
        }
    }


    /**
     * @return WebSite
     */
    private function getRequestWebsite() {
        return $this->websiteService->fetch($this->request->attributes->get('site_root_url'));
    }


    /**
     * @return ParameterBag
     */
    private function getRequestPayload() {
        if ($this->request->request->count()) {
            return $this->request->request;
        }

        if ($this->request->query->count()) {
            return $this->request->query;
        }

        return new ParameterBag();
    }


    /**
     * @return JobType
     */
    private function getRequestJobType() {
        if (!$this->jobTypeService->has($this->getRequestPayload()->get('type'))) {
            return $this->jobTypeService->getDefaultType();
        }

        return $this->jobTypeService->getByName($this->getRequestPayload()->get('type'));
    }


    private function getTaskConfigurationCollection() {
        $collection = $this->getRequestTaskConfigurationCollection();

        if ($collection->isEmpty()) {
            $selectableTaskTypes = $this->getAllSelectableTaskTypes();
            foreach ($selectableTaskTypes as $taskType) {
                $taskConfiguration = new TaskConfiguration();
                $taskConfiguration->setType($taskType);
                $collection->add($taskConfiguration);
            }
        }

        return $collection;
    }


    private function getRequestTaskConfigurationCollection() {
        $collection = new TaskConfigurationCollection();

        if (!$this->getRequestPayload()->has('test-types')) {
            return $collection;
        }

        if (!is_array($this->getRequestPayload()->get('test-types'))) {
            return $collection;
        }

        foreach ($this->getRequestPayload()->get('test-types') as $taskTypeName) {
            if ($this->taskTypeService->exists($taskTypeName)) {
                $taskType = $this->taskTypeService->getByName($taskTypeName);

                if ($taskType->isSelectable()) {
                    $taskConfiguration = new TaskConfiguration();
                    $taskConfiguration->setType($taskType);
                    $taskConfiguration->setOptions($this->getRequestTaskTypeOptions($taskType));
                    $collection->add($taskConfiguration);
                }
            }
        }

        return $collection;
    }


    /**
     * @param TaskType $taskType
     * @return array
     */
    private function getRequestTaskTypeOptions(TaskType $taskType) {
        if (!$this->getRequestPayload()->has('test-type-options')) {
            return [];
        }

        if (!is_array($this->getRequestPayload()->get('test-type-options'))) {
            return [];
        }

        $rawTaskTypeOptions = $this->getRequestPayload()->get('test-type-options');

        foreach ($rawTaskTypeOptions as $taskTypeName => $options) {
            if (strtolower(urldecode(strtolower($taskTypeName))) == strtolower($taskType->getName())) {
                return $options;
            }
        }

        return [];
    }


    /**
     *
     * @return array
     */
    private function getAllSelectableTaskTypes() {
        return $this->taskTypeService->getEntityRepository()->findBy([
            'selectable' => true
        ]);
    }


    /**
     * @return array
     */
    private function getRequestParameters() {
        if (!$this->getRequestPayload()->has('parameters')) {
            return null;
        }

        if (!is_array($this->getRequestPayload()->get('parameters'))) {
            return null;
        }

        $parameters = [];
        $rawParameters = $this->getRequestPayload()->get('parameters');

        foreach ($rawParameters as $key => $value) {
            $parameters[urldecode(strtolower($key))] = $value;
        }

        if (!count($parameters)) {
            return null;
        }

        return $parameters;
    }


    /**
     * @return bool
     */
    private function hasRequestParameters() {
        return !is_null($this->getRequestParameters());
    }
    
}