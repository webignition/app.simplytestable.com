<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

interface TaskPreprocessorInterface
{
    /**
     * @param Task $task
     *
     * @return bool
     */
    public function process(Task $task);

    /**
     * @param TaskType $taskType
     *
     * @return bool
     */
    public function handles(TaskType $taskType);
}
