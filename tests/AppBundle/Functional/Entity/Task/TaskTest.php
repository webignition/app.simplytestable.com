<?php

namespace Tests\AppBundle\Functional\Entity\Task;

use AppBundle\Services\StateService;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Factory\JobFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Entity\Task\Task;

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

        $this->jobFactory = new JobFactory(self::$container);
    }

    public function testPersistAndRetrieve()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $stateService = self::$container->get(StateService::class);

        $taskRepository = $entityManager->getRepository(Task::class);

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
