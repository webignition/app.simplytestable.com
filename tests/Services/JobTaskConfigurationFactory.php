<?php

namespace App\Tests\Services;

use App\Entity\Job\TaskConfiguration;
use App\Services\TaskTypeService;

class JobTaskConfigurationFactory
{
    const KEY_TYPE = 'type';
    const KEY_OPTIONS = 'options';

    private $taskTypeService;

    public function __construct(TaskTypeService $taskTypeService)
    {
        $this->taskTypeService = $taskTypeService;
    }

    /**
     * @param array $jobTaskConfigurationValues
     *
     * @return TaskConfiguration
     */
    public function create($jobTaskConfigurationValues)
    {
        $jobTaskConfiguration = new TaskConfiguration();

        $taskType = $this->taskTypeService->get($jobTaskConfigurationValues[self::KEY_TYPE]);

        $jobTaskConfiguration->setType($taskType);

        if (isset($jobTaskConfigurationValues[self::KEY_OPTIONS])) {
            $jobTaskConfiguration->setOptions($jobTaskConfigurationValues[self::KEY_OPTIONS]);
        }

        return $jobTaskConfiguration;
    }
}
