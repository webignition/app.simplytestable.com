<?php

namespace App\Tests\Unit\Controller\Task;

use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\TaskController;
use App\Services\TaskTypeService;
use App\Tests\Factory\MockFactory;

abstract class AbstractTaskControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $services
     *
     * @return TaskController
     */
    protected function createTaskController($services = [])
    {
        if (!isset($services[EntityManagerInterface::class])) {
            $services[EntityManagerInterface::class] = MockFactory::createEntityManager();
        }

        if (!isset($services[TaskTypeService::class])) {
            $services[TaskTypeService::class] = MockFactory::createTaskTypeService();
        }

        if (!isset($services[TaskRepository::class])) {
            $services[TaskRepository::class] = \Mockery::mock(TaskRepository::class);
        }

        $taskController = new TaskController(
            $services[EntityManagerInterface::class],
            $services[TaskTypeService::class],
            $services[TaskRepository::class]
        );

        return $taskController;
    }
}
