<?php

namespace Tests\AppBundle\Factory;

use AppBundle\Entity\Task\Type\Type as TaskType;

class TaskTypeFactory
{
    /**
     * @param string $name
     *
     * @return TaskType
     */
    public static function create($name)
    {
        $taskType = new TaskType();
        $taskType->setName($name);

        return $taskType;
    }
}
