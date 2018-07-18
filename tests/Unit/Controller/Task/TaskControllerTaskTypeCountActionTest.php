<?php

namespace App\Tests\Unit\Controller\Task;

use App\Services\TaskTypeService;
use App\Tests\Factory\MockFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group Controller/TaskController
 */
class TaskControllerTaskTypeCountActionTest extends AbstractTaskControllerTest
{
    public function testTaskTypeCountActionInvalidTaskType()
    {
        $taskTypeName = 'foo';

        $taskController = $this->createTaskController([
            TaskTypeService::class => MockFactory::createTaskTypeService([
                'get' => [
                    'with' => $taskTypeName,
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $taskController->taskTypeCountAction($taskTypeName, 'completed');
    }
}
