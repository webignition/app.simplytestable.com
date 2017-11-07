<?php

namespace Tests\ApiBundle\Functional\Entity\Task;

use SimplyTestable\ApiBundle\Services\TaskService;
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
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $taskRepository = $this->container->get('simplytestable.repository.task');

        $taskUrl = 'http://example.com/ɸ';
        $parameterKey = 'key-ɸ';
        $parameterValue = 'value-ɸ';

        $task = new Task();
        $task->setJob($this->jobFactory->create());
        $task->setUrl($taskUrl);
        $task->setState($stateService->fetch(TaskService::QUEUED_STATE));
        $task->setType($taskTypeService->getByName('HTML Validation'));
        $task->setParameters(json_encode([
            $parameterKey => $parameterValue,
        ]));

        $entityManager->persist($task);
        $entityManager->flush();

        $taskId = $task->getId();

        $entityManager->clear();

        $retrievedTask = $taskRepository->find($taskId);

        $this->assertEquals(
            $taskUrl,
            $retrievedTask->getUrl()
        );

        $this->assertEquals(
            '{"key-\u0278":"value-\u0278"}',
            $retrievedTask->getParameters()
        );
    }
}
