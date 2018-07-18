<?php
namespace AppBundle\Services\TaskOutputJoiner;

use AppBundle\Entity\Task\Output as TaskOutput;
use AppBundle\Entity\Task\Type\Type as TaskType;

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
