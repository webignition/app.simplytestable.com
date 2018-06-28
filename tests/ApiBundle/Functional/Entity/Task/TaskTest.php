<?php

namespace Tests\ApiBundle\Functional\Entity\Task;

use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Task;

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

        $this->jobFactory = new JobFactory($this->container);
    }

    public function testPersistAndRetrieve()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get(TaskTypeService::class);
        $stateService = $this->container->get(StateService::class);

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
