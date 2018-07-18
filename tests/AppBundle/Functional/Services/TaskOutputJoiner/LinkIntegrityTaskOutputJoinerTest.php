<?php

namespace Tests\AppBundle\Functional\Services\TaskOutputJoiner;

use AppBundle\Entity\Task\Output;
use AppBundle\Entity\Task\Type\Type;
use AppBundle\Services\TaskOutputJoiner\LinkIntegrityTaskOutputJoiner;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use Tests\AppBundle\Factory\JobFactory;

class LinkIntegrityTaskOutputJoinerTest extends AbstractBaseTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var LinkIntegrityTaskOutputJoiner
     */
    private $linkIntegrityTaskOutputJoiner;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory(self::$container);
        $this->linkIntegrityTaskOutputJoiner = self::$container->get(LinkIntegrityTaskOutputJoiner::class);
    }

    /**
     * @dataProvider handlesDataProvider
     *
     * @param string $taskTypeName
     * @param bool $expectedHandles
     */
    public function testHandles($taskTypeName, $expectedHandles)
    {
        $taskType = new Type();
        $taskType->setName($taskTypeName);

        $this->assertEquals(
            $expectedHandles,
            $this->linkIntegrityTaskOutputJoiner->handles($taskType)
        );
    }

    /**
     * @return array
     */
    public function handlesDataProvider()
    {
        return [
            TaskTypeService::HTML_VALIDATION_TYPE => [
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'expectedHandles' => false,
            ],
            TaskTypeService::CSS_VALIDATION_TYPE => [
                'taskTypeName' => TaskTypeService::CSS_VALIDATION_TYPE,
                'expectedHandles' => false,
            ],
            TaskTypeService::JS_STATIC_ANALYSIS_TYPE => [
                'taskTypeName' => TaskTypeService::JS_STATIC_ANALYSIS_TYPE,
                'expectedHandles' => false,
            ],
            TaskTypeService::URL_DISCOVERY_TYPE => [
                'taskTypeName' => TaskTypeService::URL_DISCOVERY_TYPE,
                'expectedHandles' => false,
            ],
            TaskTypeService::LINK_INTEGRITY_TYPE => [
                'taskTypeName' => TaskTypeService::LINK_INTEGRITY_TYPE,
                'expectedHandles' => true,
            ],
        ];
    }

    /**
     * @dataProvider joinDataProvider
     *
     * @param array $outputValuesCollection
     * @param array $expectedSerializedJoinedOutput
     */
    public function testJoin($outputValuesCollection, $expectedSerializedJoinedOutput)
    {
        $outputs = [];

        foreach ($outputValuesCollection as $outputValues) {
            $output = new Output();
            $output->setOutput($outputValues['output']);

            if (isset($outputValues['errorCount'])) {
                $output->setErrorCount($outputValues['errorCount']);
            }

            if (isset($outputValues['contentType'])) {
                $output->setContentType($outputValues['contentType']);
            }

            $outputs[] = $output;
        }

        $joinedOutput = $this->linkIntegrityTaskOutputJoiner->join($outputs);

        $this->assertEquals($expectedSerializedJoinedOutput, $joinedOutput->jsonSerialize());
    }

    /**
     * @return array
     */
    public function joinDataProvider()
    {
        return [
            'single output' => [
                'outputValuesCollection' => [
                    [
                        'output' => json_encode([
                            [
                                'context' => '<a href="http://example.com/one">Example One</a>',
                                'state' => 404,
                                'type' => 'http',
                                'url' => 'http://example.com/one'
                            ],
                        ]),
                        'errorCount' => 1,
                    ],
                ],
                'expectedSerializedJoinedOutput' => [
                    'output' => json_encode([
                        [
                            'context' => '<a href="http://example.com/one">Example One</a>',
                            'state' => 404,
                            'type' => 'http',
                            'url' => 'http://example.com/one'
                        ],
                    ]),
                    'content_type' => '',
                    'error_count' => 1,
                    'warning_count' => 0,
                ],
            ],
            'two outputs, one error each' => [
                'outputValuesCollection' => [
                    [
                        'output' => json_encode([
                            [
                                'context' => '<a href="http://example.com/one">Example One</a>',
                                'state' => 404,
                                'type' => 'http',
                                'url' => 'http://example.com/one'
                            ],
                        ]),
                        'errorCount' => 1,
                    ],
                    [
                        'output' => json_encode([
                            [
                                'context' => '<a href="http://example.com/two">Example Two</a>',
                                'state' => 404,
                                'type' => 'http',
                                'url' => 'http://example.com/two'
                            ],
                        ]),
                        'errorCount' => 1,
                    ],
                ],
                'expectedSerializedJoinedOutput' => [
                    'output' => json_encode([
                        [
                            'context' => '<a href="http://example.com/one">Example One</a>',
                            'state' => 404,
                            'type' => 'http',
                            'url' => 'http://example.com/one'
                        ],
                        [
                            'context' => '<a href="http://example.com/two">Example Two</a>',
                            'state' => 404,
                            'type' => 'http',
                            'url' => 'http://example.com/two'
                        ],
                    ]),
                    'content_type' => 'application/json',
                    'error_count' => 2,
                    'warning_count' => 0,
                ],
            ],
            'many outputs' => [
                'outputValuesCollection' => [
                    [
                        'output' => json_encode([
                            [
                                'context' => '<a href="http://example.com/one">Example One</a>',
                                'state' => 404,
                                'type' => 'http',
                                'url' => 'http://example.com/one'
                            ],
                            [
                                'context' => '<a href="http://example.com/five">Example Five</a>',
                                'state' => 500,
                                'type' => 'http',
                                'url' => 'http://example.com/five'
                            ],
                            [
                                'state' => 200,
                                'type' => 'http',
                                'url' => 'http://example.com/six'
                            ],
                        ]),
                        'errorCount' => 1,
                    ],
                    [
                        'output' => json_encode([
                            [
                                'context' => '<a href="http://example.com/one">Example One</a>',
                                'state' => 404,
                                'type' => 'http',
                                'url' => 'http://example.com/one'
                            ],
                            [
                                'context' => '<a href="http://example.com/one">Example One</a>',
                                'state' => 404,
                                'type' => 'http',
                                'url' => 'http://example.com/one'
                            ],
                            [
                                'context' => '<a href="http://example.com/two">Example Two</a>',
                                'state' => 200,
                                'type' => 'http',
                                'url' => 'http://example.com/two'
                            ],
                            [
                                'context' => '<a href="http://example.com/three">Example Three</a>',
                                'state' => 28,
                                'type' => 'curl',
                                'url' => 'http://example.com/three'
                            ],
                        ]),
                        'errorCount' => 1,
                    ],
                ],
                'expectedSerializedJoinedOutput' => [
                    'output' => json_encode([
                        [
                            'context' => '<a href="http://example.com/one">Example One</a>',
                            'state' => 404,
                            'type' => 'http',
                            'url' => 'http://example.com/one'
                        ],
                        [
                            'context' => '<a href="http://example.com/five">Example Five</a>',
                            'state' => 500,
                            'type' => 'http',
                            'url' => 'http://example.com/five'
                        ],
                        [
                            'state' => 200,
                            'type' => 'http',
                            'url' => 'http://example.com/six'
                        ],
                        [
                            'context' => '<a href="http://example.com/two">Example Two</a>',
                            'state' => 200,
                            'type' => 'http',
                            'url' => 'http://example.com/two'
                        ],
                        [
                            'context' => '<a href="http://example.com/three">Example Three</a>',
                            'state' => 28,
                            'type' => 'curl',
                            'url' => 'http://example.com/three'
                        ],
                    ]),
                    'content_type' => 'application/json',
                    'error_count' => 3,
                    'warning_count' => 0,
                ],
            ],
        ];
    }
}
