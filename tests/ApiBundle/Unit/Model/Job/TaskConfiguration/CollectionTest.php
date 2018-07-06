<?php

namespace Tests\ApiBundle\Unit\Model\Job\TaskConfiguration;

use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\ModelFactory;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TaskConfigurationCollection
     */
    private $taskConfigurationCollection;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskConfigurationCollection = new TaskConfigurationCollection();
    }

    /**
     * @dataProvider addDataProvider
     *
     * @param array $taskConfigurationValuesCollection
     * @param array $expectedTaskConfigurationValuesCollection
     */
    public function testAdd($taskConfigurationValuesCollection, $expectedTaskConfigurationValuesCollection)
    {
        foreach ($taskConfigurationValuesCollection as $taskConfigurationValues) {
            $taskConfiguration = ModelFactory::createTaskConfiguration($taskConfigurationValues);
            $this->taskConfigurationCollection->add($taskConfiguration);
        }

        $taskConfigurations = $this->taskConfigurationCollection->get();

        $this->assertCount(
            count($expectedTaskConfigurationValuesCollection),
            $taskConfigurations
        );

        foreach ($expectedTaskConfigurationValuesCollection as $expectedTaskConfigurationValues) {
            $expectedTaskConfiguration = ModelFactory::createTaskConfiguration($expectedTaskConfigurationValues);
            $this->assertTrue($this->taskConfigurationCollection->contains($expectedTaskConfiguration));
        }
    }

    /**
     * @return array
     */
    public function addDataProvider()
    {
        return [
            'none' => [
                'taskConfigurationValuesCollection' => [],
                'expectedTaskConfigurationValuesCollection' => [],
            ],
            'single' => [
                'taskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                            'html-foo' => 'html-bar',
                        ],
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => true,
                    ],
                ],
                'expectedTaskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                            'html-foo' => 'html-bar',
                        ],
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => true,
                    ],
                ],
            ],
            'multiple with duplicates not added' => [
                'taskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                            'html-foo' => 'html-bar',
                        ],
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => true,
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                            'html-foo' => 'html-bar',
                        ],
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => true,
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                            'html-foo' => 'html-bar',
                        ],
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => false,
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                            'css-foo' => 'css-bar',
                        ],
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => true,
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => true,
                    ],
                ],
                'expectedTaskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                            'html-foo' => 'html-bar',
                        ],
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => true,
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                            'html-foo' => 'html-bar',
                        ],
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => false,
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                            'css-foo' => 'css-bar',
                        ],
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => true,
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => true,
                    ],
                ],
            ],
        ];
    }

    public function testGet()
    {
        $taskConfigurations = $this->taskConfigurationCollection->get();

        $this->assertInternalType('array', $taskConfigurations);
    }

    /**
     * @dataProvider getEnabledDataProvider
     *
     * @param array $taskConfigurationValuesCollection
     * @param array $expectedTaskConfigurationValuesCollection
     */
    public function testGetEnabled($taskConfigurationValuesCollection, $expectedTaskConfigurationValuesCollection)
    {
        foreach ($taskConfigurationValuesCollection as $taskConfigurationValues) {
            $taskConfiguration = ModelFactory::createTaskConfiguration($taskConfigurationValues);
            $this->taskConfigurationCollection->add($taskConfiguration);
        }

        $retrievedTaskConfigurations = $this->taskConfigurationCollection->getEnabled();

        $this->assertCount(
            count($expectedTaskConfigurationValuesCollection),
            $retrievedTaskConfigurations
        );

        foreach ($expectedTaskConfigurationValuesCollection as $index => $expectedTaskConfigurationValues) {
            $taskConfiguration = $retrievedTaskConfigurations[$index];
            $this->assertEquals($expectedTaskConfigurationValues['type'], $taskConfiguration->getType()->getName());
            $this->assertTrue($taskConfiguration->getIsEnabled());
        }
    }

    /**
     * @return array
     */
    public function getEnabledDataProvider()
    {
        return [
            'multiple with duplicates not added' => [
                'taskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => true,
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => false,
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::LINK_INTEGRITY_TYPE,
                        ModelFactory::TASK_CONFIGURATION_IS_ENABLED => true,
                    ],
                ],
                'expectedTaskConfigurationValuesCollection' => [
                    [
                        'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                    [
                        'type' => TaskTypeService::LINK_INTEGRITY_TYPE,
                    ],
                ],
            ],
        ];
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->taskConfigurationCollection->isEmpty());

        $this->taskConfigurationCollection->add(ModelFactory::createTaskConfiguration([
            ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
        ]));

        $this->assertFalse($this->taskConfigurationCollection->isEmpty());
    }

    /**
     * @dataProvider equalsDataProvider
     *
     * @param array $taskConfigurationValuesCollection
     * @param array $comparatorTaskConfigurationValuesCollection
     * @param bool $expectedEquals
     */
    public function testEquals(
        $taskConfigurationValuesCollection,
        $comparatorTaskConfigurationValuesCollection,
        $expectedEquals
    ) {
        $taskConfigurationCollection = ModelFactory::createTaskConfigurationCollection(
            $taskConfigurationValuesCollection
        );

        $comparatorTaskConfigurationCollection = ModelFactory::createTaskConfigurationCollection(
            $comparatorTaskConfigurationValuesCollection
        );

        $this->assertEquals(
            $expectedEquals,
            $taskConfigurationCollection->equals($comparatorTaskConfigurationCollection)
        );
    }

    /**
     * @return array
     */
    public function equalsDataProvider()
    {
        return [
            'non-equal count' => [
                'taskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                    ],
                ],
                'comparatorTaskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
                'expectedEquals' => false,
            ],
            'non-equal matches' => [
                'taskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
                'comparatorTaskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                    ],
                ],
                'expectedEquals' => false,
            ],
            'equals' => [
                'taskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
                'comparatorTaskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
                'expectedEquals' => true,
            ],
        ];
    }

    /**
     * @dataProvider getTaskTypesDataProvider
     *
     * @param array $taskConfigurationValuesCollection
     * @param string[] $expectedTaskTypeNames
     */
    public function testGetTaskTypes(
        $taskConfigurationValuesCollection,
        $expectedTaskTypeNames
    ) {
        $taskConfigurationCollection = ModelFactory::createTaskConfigurationCollection(
            $taskConfigurationValuesCollection
        );

        $taskTypes = $taskConfigurationCollection->getTaskTypes();

        $taskTypeNames = [];
        foreach ($taskTypes->get() as $taskType) {
            $taskTypeNames[] = $taskType->getName();
        }

        $this->assertEquals($expectedTaskTypeNames, $taskTypeNames);
    }

    /**
     * @return array
     */
    public function getTaskTypesDataProvider()
    {
        return [
            'none' => [
                'taskConfigurationValuesCollection' => [],
                'expectedTaskTypeNames' => [],
            ],
            'two' => [
                'taskConfigurationValuesCollection' => [
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                    [
                        ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                    ],
                ],
                'expectedTaskTypeNames' => [
                    TaskTypeService::HTML_VALIDATION_TYPE,
                    TaskTypeService::CSS_VALIDATION_TYPE,
                ],
            ],
        ];
    }
}
