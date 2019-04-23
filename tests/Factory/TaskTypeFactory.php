<?php

namespace App\Tests\Factory;

use App\Entity\Task\TaskType;

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
