<?php
namespace App\Services\TaskPostProcessor;

use App\Entity\Task\Task;
use App\Entity\Task\Type\Type as TaskType;

interface TaskPostProcessorInterface
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
