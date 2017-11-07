<?php

namespace SimplyTestable\ApiBundle\Adapter\Job\TaskConfiguration;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Services\TaskTypeService;

class RequestAdapter
{
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
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        $this->collection = new TaskConfigurationCollection();
    }

    /**
     * @param TaskTypeService $taskTypeService
     */
    public function setTaskTypeService(TaskTypeService $taskTypeService)
    {
        $this->taskTypeService = $taskTypeService;
    }

    /**
     * @return TaskConfigurationCollection
     */
    public function getCollection()
    {
        if ($this->collection->isEmpty()) {
            $this->build();
        }

        return $this->collection;
    }

    private function build()
    {
        if (is_null($this->request->get(self::REQUEST_TASK_CONFIGURATION_KEY))) {
            return;
        }

        if (!is_array($this->request->get(self::REQUEST_TASK_CONFIGURATION_KEY))) {
            return;
        }

        foreach ($this->request->get(self::REQUEST_TASK_CONFIGURATION_KEY) as $taskTypeName => $taskTypeOptions) {
            $taskType = $this->taskTypeService->get($taskTypeName);

            if (empty($taskType)) {
                continue;
            }

            if (!$taskType->getSelectable()) {
                continue;
            }

            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType($taskType);

            if (array_key_exists(self::REQUEST_IS_ENABLED_KEY, $taskTypeOptions)) {
                $taskConfiguration->setIsEnabled(
                    filter_var($taskTypeOptions[self::REQUEST_IS_ENABLED_KEY], FILTER_VALIDATE_BOOLEAN)
                );
                unset($taskTypeOptions[self::REQUEST_IS_ENABLED_KEY]);
            }

            $taskConfiguration->setOptions($taskTypeOptions);
            $this->collection->add($taskConfiguration);
        }
    }
}
