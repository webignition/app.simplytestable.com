<?php
namespace App\Services\TaskOutputJoiner;

use App\Entity\Task\Output as TaskOutput;
use App\Entity\Task\TaskType;

interface TaskOutputJoinerInterface
{
    /**
     * @param TaskOutput[] $outputs
     *
     * @return TaskOutput
     */
    public function join($outputs);

    /**
     * @param TaskType $taskType
     *
     * @return bool
     */
    public function handles(TaskType $taskType);
}
