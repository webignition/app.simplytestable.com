<?php
namespace SimplyTestable\ApiBundle\Services\TaskOutputJoiner;

use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

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
