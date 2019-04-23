<?php

namespace App\Tests\Functional\Adapter\Job\TaskConfiguration\RequestAdapter;

use Mockery\Mock;
use App\Adapter\Job\TaskConfiguration\RequestAdapter;
use App\Entity\Task\TaskType;
use App\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\Request;

class RequestAdapterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getCollectionDataProvider
     *
     * @param Request $request
     * @param TaskTypeService $taskTypeService
     * @param bool $expectedIsEmpty
     * @param array $expectedTaskConfigurationCollection
     */
    public function testGetCollection(
        Request $request,
        TaskTypeService $taskTypeService,
        $expectedIsEmpty,
        $expectedTaskConfigurationCollection
    ) {
        $requestAdapter = new RequestAdapter();
        $requestAdapter->setRequest($request);
        $requestAdapter->setTaskTypeService($taskTypeService);

        $taskConfigurationCollection = $requestAdapter->getCollection();

        $this->assertEquals($expectedIsEmpty, $taskConfigurationCollection->isEmpty());
        $this->assertCount(count($expectedTaskConfigurationCollection), $taskConfigurationCollection);

        foreach ($taskConfigurationCollection as $taskConfiguration) {
            $expectedTaskConfiguration = $expectedTaskConfigurationCollection[$taskConfiguration->getType()->getName()];
            $this->assertEquals($expectedTaskConfiguration['isEnabled'], $taskConfiguration->getIsEnabled());
            $this->assertEquals($expectedTaskConfiguration['options'], $taskConfiguration->getOptions());
        }
    }

    /**
     * @return array
     */
    public function getCollectionDataProvider()
    {
        return [
            'missing task configuration' => [
                'request' => new Request(),
                'taskTypeService' => $this->createTaskTypeService(),
                'expectedIsEmpty' => true,
                'expectedTaskConfigurationCollection' => [],
            ],
            'task configuration is wrong type' => [
                'request' => new Request([
                    RequestAdapter::REQUEST_TASK_CONFIGURATION_KEY => 'foo',
                ]),
                'taskTypeService' => $this->createTaskTypeService(),
                'expectedIsEmpty' => true,
                'expectedTaskConfigurationCollection' => [],
            ],
            'empty task configuration' => [
                'request' => new Request([
                    RequestAdapter::REQUEST_TASK_CONFIGURATION_KEY => [],
                ]),
                'taskTypeService' => $this->createTaskTypeService(),
                'expectedIsEmpty' => true,
                'expectedTaskConfigurationCollection' => [],
            ],
            'invalid task type' => [
                'request' => new Request([
                    RequestAdapter::REQUEST_TASK_CONFIGURATION_KEY => [
                        'foo' => [],
                    ],
                ]),
                'taskTypeService' => $this->createTaskTypeService([
                    'foo' => null,
                ]),
                'expectedIsEmpty' => true,
                'expectedTaskConfigurationCollection' => [],
            ],
            'unselectable task type' => [
                'request' => new Request([
                    RequestAdapter::REQUEST_TASK_CONFIGURATION_KEY => [
                        'foo' => [],
                    ],
                ]),
                'taskTypeService' => $this->createTaskTypeService([
                    'foo' => $this->createTaskType([
                        'name' => 'foo',
                        'selectable' => false,
                    ]),
                ]),
                'expectedIsEmpty' => true,
                'expectedTaskConfigurationCollection' => [],
            ],
            'enabled and disabled task types' => [
                'request' => new Request([
                    RequestAdapter::REQUEST_TASK_CONFIGURATION_KEY => [
                        'html validation' => [
                            RequestAdapter::REQUEST_IS_ENABLED_KEY => true,
                            'foo' => 'bar',
                        ],
                        'link integrity' => [
                            RequestAdapter::REQUEST_IS_ENABLED_KEY => false,
                            'bar' => 'foo',
                        ],
                    ],
                ]),
                'taskTypeService' => $this->createTaskTypeService([
                    'html validation' => $this->createTaskType([
                        'name' => 'html validation',
                        'selectable' => true,
                    ]),
                    'link integrity' => $this->createTaskType([
                        'name' => 'link integrity',
                        'selectable' => true,
                    ]),
                ]),
                'expectedIsEmpty' => false,
                'expectedTaskConfigurationCollection' => [
                    'html validation' => [
                        'isEnabled' => true,
                        'options' => [
                            'foo' => 'bar',
                        ],
                    ],
                    'link integrity' => [
                        'isEnabled' => false,
                        'options' => [
                            'bar' => 'foo',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $taskTypes
     *
     * @return Mock|TaskTypeService
     */
    private function createTaskTypeService($taskTypes = [])
    {
        /* @var Mock|TaskTypeService $taskTypeService */
        $taskTypeService = \Mockery::mock(TaskTypeService::class);

        foreach ($taskTypes as $taskTypeName => $taskType) {
            $taskTypeService
                ->shouldReceive('get')
                ->with($taskTypeName)
                ->andReturn($taskType);
        }

        return $taskTypeService;
    }

    /**
     * @param array $taskTypeValues
     *
     * @return TaskType
     */
    private function createTaskType($taskTypeValues)
    {
        $taskType = new TaskType();
        $taskType->setName($taskTypeValues['name']);
        $taskType->setSelectable($taskTypeValues['selectable']);

        return $taskType;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
