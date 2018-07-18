<?php

namespace Tests\AppBundle\Unit\Controller\Worker;

use AppBundle\Controller\Worker\TasksController;
use Tests\AppBundle\Factory\MockFactory;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @group Controller/Worker/TasksController
 */
class TasksControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TasksController
     */
    private $tasksController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->tasksController = new TasksController();
    }

    public function testRequestActionInMaintenanceReadOnlyMode()
    {
        $this->expectException(ServiceUnavailableHttpException::class);

        $this->tasksController->requestAction(
            MockFactory::createApplicationStateService(true),
            MockFactory::createEntityManager(),
            MockFactory::createResqueQueueService(),
            MockFactory::createStateService(),
            MockFactory::createTaskQueueService(),
            new Request()
        );
    }
}
