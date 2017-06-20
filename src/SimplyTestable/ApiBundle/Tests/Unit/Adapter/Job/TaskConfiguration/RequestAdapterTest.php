<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Adapter\Job\TaskConfiguration\RequestAdapter;

use Mockery\MockInterface;
use SimplyTestable\ApiBundle\Adapter\Job\TaskConfiguration\RequestAdapter;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\Request;

class RequestAdapterTest extends \PHPUnit_Framework_TestCase
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
        $this->assertCount(count($expectedTaskConfigurationCollection), $taskConfigurationCollection->get());

        foreach ($taskConfigurationCollection->get() as $taskConfiguration) {
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
                    'foo' => [
                        'exists' => false,
                    ],
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
                    'foo' => [
                        'exists' => true,
                        'taskType' => $this->createTaskType([
                            'name' => 'foo',
                            'selectable' => false,
                        ]),
                    ],
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
                    'html validation' => [
                        'exists' => true,
                        'taskType' => $this->createTaskType([
                            'name' => 'html validation',
                            'selectable' => true,
                        ]),
                    ],
                    'link integrity' => [
                        'exists' => true,
                        'taskType' => $this->createTaskType([
                            'name' => 'link integrity',
                            'selectable' => true,
                        ]),
                    ],
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
     * @return MockInterface|TaskTypeService
     */
    private function createTaskTypeService($taskTypes = [])
    {
        $taskTypeService = \Mockery::mock(TaskTypeService::class);

        foreach ($taskTypes as $taskTypeName => $taskTypeProperties) {
            $taskTypeService
                ->shouldReceive('exists')
                ->with($taskTypeName)
                ->andReturn($taskTypeProperties['exists']);

            if ($taskTypeProperties['exists']) {
                $taskTypeService
                    ->shouldReceive('getByName')
                    ->with($taskTypeName)
                    ->andReturn($taskTypeProperties['taskType']);
            }
        }

        return $taskTypeService;
    }

    /**
     * @param array $taskTypeValues
     *
     * @return Type
     */
    private function createTaskType($taskTypeValues)
    {
        $taskType = new Type();
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