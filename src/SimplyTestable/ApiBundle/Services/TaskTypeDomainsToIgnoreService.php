<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class TaskTypeDomainsToIgnoreService
{
    /**
     * @var array
     */
    private $domainsToIgnoreByTaskType;

    /**
     * @param array $domainsToIgnoreByTaskType
     */
    public function __construct($domainsToIgnoreByTaskType)
    {
        $this->domainsToIgnoreByTaskType = $domainsToIgnoreByTaskType;
    }

    /**
     * @param TaskType $taskType
     *
     * @return array
     */
    public function getForTaskType(TaskType $taskType)
    {
        $taskTypeKey = strtolower($taskType->getName());

        return isset($this->domainsToIgnoreByTaskType[$taskTypeKey])
            ? $this->domainsToIgnoreByTaskType[$taskTypeKey]
            : [];
    }
}
