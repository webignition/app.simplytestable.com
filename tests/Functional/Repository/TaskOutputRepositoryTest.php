<?php

namespace App\Tests\Functional\Services;

use App\Entity\Task\Task;
use App\Repository\TaskOutputRepository;
use App\Services\TaskTypeService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\TaskOutputFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;

class TaskOutputRepositoryTest extends AbstractBaseTestCase
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

        $this->taskOutputRepository = self::$container->get(TaskOutputRepository::class);
        $this->jobFactory = new JobFactory(self::$container);
        $this->userFactory = new UserFactory(self::$container);
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
        $taskTypeService = self::$container->get(TaskTypeService::class);

        $jobFactory = new JobFactory(self::$container);
        $taskOutputFactory = new TaskOutputFactory(self::$container);

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
                $taskOutput = $task->getOutput();

                if (!empty($taskOutput)) {
                    $taskOutputId = $taskOutput->getId();

                    if (!in_array($taskOutputId, $expectedTaskOutputIds)) {
                        $expectedTaskOutputIds[] = $taskOutputId;
                    }
                }
            }
        }

        $taskType = $taskTypeService->get($taskTypeName);

        $taskOutputIds = $this->taskOutputRepository->findIdsByTaskType($taskType, $limit, $offset);

        $this->assertCount(count($expectedTaskOutputIndices), $expectedTaskOutputIds);
        $this->assertEquals($expectedTaskOutputIds, $taskOutputIds);
    }

    /**
     * @return array
     */
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
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $jobFactory = new JobFactory(self::$container);
        $taskOutputFactory = new TaskOutputFactory(self::$container);

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
                $taskOutput = $task->getOutput();

                if (!empty($taskOutput)) {
                    $taskOutputId = $taskOutput->getId();

                    if (!in_array($taskOutputId, $expectedTaskOutputIds)) {
                        $expectedTaskOutputIds[] = $taskOutputId;
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

    /**
     * @return array
     */
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

    /**
     * @dataProvider findHashlessOutputIdsDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param int[] $taskOutputIdsToRemoveHashFor
     * @param int $limit
     * @param int [] $expectedTaskOutputIndices
     */
    public function testFindHashlessOutputIds(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $taskOutputIdsToRemoveHashFor,
        $limit,
        $expectedTaskOutputIndices
    ) {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $jobFactory = new JobFactory(self::$container);
        $taskOutputFactory = new TaskOutputFactory(self::$container);

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
                $taskOutput = $task->getOutput();

                if (!empty($taskOutput)) {
                    $taskOutputId = $taskOutput->getId();

                    if (!in_array($taskOutputId, $expectedTaskOutputIds)) {
                        $expectedTaskOutputIds[] = $taskOutputId;
                    }
                }
            }

            if (in_array($taskIndex, $taskOutputIdsToRemoveHashFor)) {
                $output = $task->getOutput();
                $output->setHash(null);

                $entityManager->persist($output);
                $entityManager->flush($output);
            }
        }

        $taskOutputIds = $this->taskOutputRepository->findHashlessOutputIds($limit);

        $this->assertCount(count($expectedTaskOutputIndices), $expectedTaskOutputIds);
        $this->assertEquals($expectedTaskOutputIds, $taskOutputIds);
    }

    /**
     * @return array
     */
    public function findHashlessOutputIdsDataProvider()
    {
        return [
            'no hashless output' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                ],
                'taskOutputIdsToRemoveHashFor' => [],
                'limit' => null,
                'expectedTaskOutputIndices' => [],
            ],
            'single hashless output' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                ],
                'taskOutputIdsToRemoveHashFor' => [1],
                'limit' => null,
                'expectedTaskOutputIndices' => [1],
            ],
            'all hashless output' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                ],
                'taskOutputIdsToRemoveHashFor' => [0, 1, 2],
                'limit' => null,
                'expectedTaskOutputIndices' => [0, 1, 2],
            ],
            'with limit' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                ],
                'taskIndicesToRemoveOutputFor' => [0, 1, 2],
                'limit' => 2,
                'expectedTaskOutputIndices' => [0, 1],
            ],
        ];
    }

    /**
     * @dataProvider findDuplicateHashesDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param int $limit
     * @param int [] $expectedHashes
     */
    public function testFindDuplicateHashes(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $limit,
        $expectedHashes
    ) {
        $jobFactory = new JobFactory(self::$container);
        $taskOutputFactory = new TaskOutputFactory(self::$container);

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

        $hashes = $this->taskOutputRepository->findDuplicateHashes($limit);

        $this->assertEquals($expectedHashes, $hashes);
    }

    /**
     * @return array
     */
    public function findDuplicateHashesDataProvider()
    {
        return [
            'no duplicate hashes' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                ],
                'limit' => null,
                'expectedHashes' => [],
            ],
            'has duplicate hashes' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'unique0',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'unique1',
                    ],
                ],
                'limit' => null,
                'expectedHashes' => [
                    'aa9c623dd8063fc506e5c3deb4dbf53b',
                    'c14228d1a59431ee9a24b0647d579342',
                ],
            ],
            'with limit' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                ],
                'limit' => 2,
                'expectedHashes' => [
                    '8428409b5a270439475117358fdabd5c',
                    'aa9c623dd8063fc506e5c3deb4dbf53b',
                ],
            ],
        ];
    }

    /**
     * @dataProvider findIdsByHashDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param string $hash
     * @param int [] $expectedTaskOutputIndices
     */
    public function testFindIdsByHash(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $hash,
        $expectedTaskOutputIndices
    ) {
        $jobFactory = new JobFactory(self::$container);
        $taskOutputFactory = new TaskOutputFactory(self::$container);

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
                $taskOutput = $task->getOutput();

                if (!empty($taskOutput)) {
                    $taskOutputId = $taskOutput->getId();

                    if (!in_array($taskOutputId, $expectedTaskOutputIds)) {
                        $expectedTaskOutputIds[] = $taskOutputId;
                    }
                }
            }
        }

        $taskOutputIds = $this->taskOutputRepository->findIdsByHash($hash);

        $this->assertCount(count($expectedTaskOutputIndices), $expectedTaskOutputIds);
        $this->assertEquals($expectedTaskOutputIds, $taskOutputIds);
    }

    /**
     * @return array
     */
    public function findIdsByHashDataProvider()
    {
        return [
            'no hashless output' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foobar',
                    ],
                ],
                'hash' => 'foo',
                'expectedTaskOutputIndices' => [],
            ],
            'matches' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => 'foohash',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'foo',
                        TaskOutputFactory::KEY_HASH => 'foohash',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'bar',
                        TaskOutputFactory::KEY_HASH => 'barhash',
                    ],
                ],
                'hash' => 'foohash',
                'expectedTaskOutputIndices' => [0, 1],
            ],
        ];
    }
}
