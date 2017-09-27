<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\TaskOutputRepository;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskOutputFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class TaskOutputRepositoryTest extends BaseSimplyTestableTestCase
{
    /**
     * @var TaskOutputRepository
     */
    private $taskOutputRepository;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskOutputRepository = $this->getManager()->getRepository(Output::class);
        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }

    /**
     * @dataProvider findIdsByTaskTypeDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param string $taskTypeName
     * @param int $limit
     * @param int $offset
     * @param int []$expectedTaskOutputIndices
     */
    public function testFindIdsByTaskType(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $taskTypeName,
        $limit,
        $offset,
        $expectedTaskOutputIndices
    ) {
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        $jobFactory = new JobFactory($this->container);
        $taskOutputFactory = new TaskOutputFactory($this->container);

        $jobs = $jobFactory->createResolveAndPrepareCollection($jobValuesCollection);

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($jobs as $job) {
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex]) && !is_null($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];
                $taskOutputFactory->create($task, $taskOutputValues);
            }
        }

        $expectedTaskOutputIds = [];
        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskOutputIndices)) {
                if ($task->hasOutput()) {
                    $taskOutputId = $task->getOutput()->getId();

                    if (!in_array($taskOutputId, $expectedTaskOutputIds)) {
                        $expectedTaskOutputIds[] = $task->getOutput()->getId();
                    }
                }
            }
        }

        $taskType = $taskTypeService->getByName($taskTypeName);

        $taskOutputIds = $this->taskOutputRepository->findIdsByTaskType($taskType, $limit, $offset);

        $this->assertCount(count($expectedTaskOutputIndices), $expectedTaskOutputIds);
        $this->assertEquals($expectedTaskOutputIds, $taskOutputIds);
    }

    public function findIdsByTaskTypeDataProvider()
    {
        return [
            'no output' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'limit' => null,
                'offset' =>  null,
                'expectedTaskOutputIndices' => [],
            ],
            'no output for given type' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [],
                    [],
                    [],
                ],
                'taskTypeName' => TaskTypeService::CSS_VALIDATION_TYPE,
                'limit' => null,
                'offset' =>  null,
                'expectedTaskOutputIndices' => [],
            ],
            'has output for given type' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [],
                    null,
                    [],
                ],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'limit' => null,
                'offset' =>  null,
                'expectedTaskOutputIndices' => [0, 2],
            ],
            'with limit and offset' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo.example.com',
                        JobFactory::KEY_DOMAIN => 'foo.example.com',
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://bar.example.com',
                        JobFactory::KEY_DOMAIN => 'bar.example.com',
                    ],
                ],
                'taskOutputValuesCollection' => [
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                ],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'limit' => 3,
                'offset' =>  2,
                'expectedTaskOutputIndices' => [2, 3, 4],
            ],
        ];
    }

    /**
     * @dataProvider findUnusedIdsDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param int[] $taskIndicesToRemoveOutputFor
     * @param int $limit
     * @param int [] $expectedTaskOutputIndices
     */
    public function testFindUnusedIds(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $taskIndicesToRemoveOutputFor,
        $limit,
        $expectedTaskOutputIndices
    ) {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $jobFactory = new JobFactory($this->container);
        $taskOutputFactory = new TaskOutputFactory($this->container);

        $jobs = $jobFactory->createResolveAndPrepareCollection($jobValuesCollection);

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($jobs as $job) {
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex]) && !is_null($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];
                $taskOutputFactory->create($task, $taskOutputValues);
            }
        }

        $taskIdsToRemoveOutputFor = [];
        $expectedTaskOutputIds = [];
        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskOutputIndices)) {
                if ($task->hasOutput()) {
                    $taskOutputId = $task->getOutput()->getId();

                    if (!in_array($taskOutputId, $expectedTaskOutputIds)) {
                        $expectedTaskOutputIds[] = $task->getOutput()->getId();
                    }
                }
            }

            if (in_array($taskIndex, $taskIndicesToRemoveOutputFor)) {
                $task->setOutput(null);

                $entityManager->persist($task);
                $entityManager->flush($task);
            }
        }

        $taskOutputIds = $this->taskOutputRepository->findUnusedIds($limit);

        $this->assertCount(count($expectedTaskOutputIndices), $expectedTaskOutputIds);
        $this->assertEquals($expectedTaskOutputIds, $taskOutputIds);
    }

    public function findUnusedIdsDataProvider()
    {
        return [
            'single unused output' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [],
                    [],
                    [],
                ],
                'taskIndicesToRemoveOutputFor' => [1],
                'limit' => null,
                'expectedTaskOutputIndices' => [1],
            ],
            'all unused output' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [],
                    [],
                    [],
                ],
                'taskIndicesToRemoveOutputFor' => [0, 1, 2],
                'limit' => null,
                'expectedTaskOutputIndices' => [0, 1, 2],
            ],
            'with limit' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [],
                    [],
                    [],
                ],
                'taskIndicesToRemoveOutputFor' => [0, 1, 2],
                'limit' => 2,
                'expectedTaskOutputIndices' => [0, 1],
            ],
        ];
    }
}
