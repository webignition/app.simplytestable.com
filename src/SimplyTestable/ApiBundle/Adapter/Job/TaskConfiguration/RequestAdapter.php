<?php

namespace SimplyTestable\ApiBundle\Adapter\Job\TaskConfiguration;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Services\TaskTypeService;

class RequestAdapter {

    const REQUEST_TASK_CONFIGURATION_KEY = 'task-configuration';
    const REQUEST_IS_ENABLED_KEY = 'is-enabled';

    /**
     * @var Request
     */
    private $request;


    /**
     * @var TaskConfigurationCollection
     */
    private $collection;


    /**
     * @var TaskTypeService
     */
    private $taskTypeService;


    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request) {
        $this->request = $request;
        $this->collection = new TaskConfigurationCollection();
        return $this;
    }


    /**
     * @param TaskTypeService $taskTypeService
     * @return $this
     */
    public function setTaskTypeService(TaskTypeService $taskTypeService) {
        $this->taskTypeService = $taskTypeService;
        return $this;
    }


    /**
     * @return TaskConfigurationCollection
     */
    public function getCollection() {
        if ($this->collection->isEmpty()) {
            $this->build();
        }

        return $this->collection;
    }


    private function build() {
        if (is_null($this->request->get(self::REQUEST_TASK_CONFIGURATION_KEY))) {
            return;
        }

        if (!is_array($this->request->get(self::REQUEST_TASK_CONFIGURATION_KEY))) {
            return;
        }

        foreach ($this->request->get(self::REQUEST_TASK_CONFIGURATION_KEY) as $taskTypeName => $taskTypeOptions) {
            if (!$this->taskTypeService->exists($taskTypeName)) {
                continue;
            }

            $taskType = $this->taskTypeService->getByName($taskTypeName);
            if (!$taskType->isSelectable()) {
                continue;
            }

            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType($taskType);

            if (array_key_exists(self::REQUEST_IS_ENABLED_KEY, $taskTypeOptions)) {
                $taskConfiguration->setIsEnabled(filter_var($taskTypeOptions[self::REQUEST_IS_ENABLED_KEY], FILTER_VALIDATE_BOOLEAN));
                unset($taskTypeOptions[self::REQUEST_IS_ENABLED_KEY]);
            }

            $taskConfiguration->setOptions($taskTypeOptions);
            $this->collection->add($taskConfiguration);
        }
    }
    
}