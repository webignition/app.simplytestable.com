<?php

namespace App\Tests\Functional\Entity\Task;

use App\Repository\TaskRepository;
use App\Services\StateService;
use App\Services\TaskTypeService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Task\Task;
use App\Tests\Services\JobFactory;
use Doctrine\ORM\EntityManagerInterface;

class TaskTest extends AbstractBaseTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = self::$container->get(JobFactory::class);
    }

    public function testPersistAndRetrieve()
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $stateService = self::$container->get(StateService::class);
        $taskRepository = self::$container->get(TaskRepository::class);

        $taskUrl = 'http://example.com/ɸ';
        $parameterKey = 'key-ɸ';
        $parameterValue = 'value-ɸ';

        $task = new Task();
        $task->setJob($this->jobFactory->create());
        $task->setUrl($taskUrl);
        $task->setState($stateService->get(Task::STATE_QUEUED));
        $task->setType($taskTypeService->getHtmlValidationTaskType());
        $task->setParameters(json_encode([
            $parameterKey => $parameterValue,
        ]));

        $entityManager->persist($task);
        $entityManager->flush();

        $taskId = $task->getId();

        $entityManager->clear();

        /* @var Task $retrievedTask */
        $retrievedTask = $taskRepository->find($taskId);

        $this->assertEquals(
            $taskUrl,
            $retrievedTask->getUrl()
        );

        $this->assertEquals(
            '{"key-\u0278":"value-\u0278"}',
            $retrievedTask->getParametersString()
        );
    }
}
