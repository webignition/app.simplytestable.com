<?php

namespace Tests\ApiBundle\Functional\Controller\Task;

use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group Controller/TaskController
 */
class TaskControllerTaskTypeCountActionTest extends AbstractTaskControllerTest
{
    public function testTaskTypeCountActionInvalidState()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->taskController->taskTypeCountAction(TaskTypeService::HTML_VALIDATION_TYPE, 'foo');
    }

    public function testTaskTypeCountActionSuccess()
    {
        $response = $this->taskController->taskTypeCountAction(TaskTypeService::HTML_VALIDATION_TYPE, 'completed');

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $responseData = json_decode($response->getContent());

        $this->assertEquals(0, $responseData);
    }
}
