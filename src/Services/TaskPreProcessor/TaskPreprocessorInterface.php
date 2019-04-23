<?php
namespace App\Services\TaskPreProcessor;

use App\Entity\Task\Task;
use App\Entity\Task\TaskType;

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
