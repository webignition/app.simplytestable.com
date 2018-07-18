<?php
namespace AppBundle\Services\TaskPreProcessor;

use AppBundle\Entity\Task\Task;
use AppBundle\Entity\Task\Type\Type as TaskType;

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
