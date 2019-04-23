<?php

namespace App\Tests\Functional\Services\TaskPreProcessor;

use App\Tests\Services\JobFactory;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use App\Entity\Task\Task;
use App\Entity\Task\TaskType;
use App\Services\HttpClientService;
use App\Services\TaskPreProcessor\LinkIntegrityTaskPreProcessor;
use App\Services\TaskTypeService;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Services\TaskFactory;
use App\Tests\Services\TaskOutputFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestHttpClientService;

class LinkIntegrityTaskPreprocessorTest extends AbstractBaseTestCase
{
    /**
     * @var LinkIntegrityTaskPreProcessor
     */
    private $linkIntegrityTaskPreProcessor;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    /**
     * @var TaskOutputFactory
     */
    private $taskOutputFactory;

    /**
     * @var TestHttpClientService
     */
    private $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->linkIntegrityTaskPreProcessor = self::$container->get(LinkIntegrityTaskPreProcessor::class);

        $this->jobFactory = self::$container->get(JobFactory::class);
        $this->taskFactory = self::$container->get(TaskFactory::class);
        $this->taskOutputFactory = self::$container->get(TaskOutputFactory::class);
        $this->httpClientService = self::$container->get(HttpClientService::class);
    }

    /**
     * @dataProvider handlesDataProvider
     *
     * @param string $taskTypeName
     * @param bool $expectedHandles
     */
    public function testHandles($taskTypeName, $expectedHandles)
    {
        $taskType = new TaskType();
        $taskType->setName($taskTypeName);

        $this->assertEquals(
            $expectedHandles,
            $this->linkIntegrityTaskPreProcessor->handles($taskType)
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
     * @dataProvider processWebResourceFailureDataProvider
     *
     * @param array $httpFixtures
     */
    public function testProcessWebResourceFailure($httpFixtures)
    {
        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => [
                TaskTypeService::LINK_INTEGRITY_TYPE,
            ],
        ]);

        $tasks = $job->getTasks();

        /* @var Task $selectedTask */
        $selectedTask = $tasks->get(0);
        $selectedTaskOutput = $selectedTask->getOutput();
        $this->assertEmpty($selectedTaskOutput);

        $taskWithOutput = $tasks->get(1);

        $this->taskFactory->setEndDateTime($taskWithOutput, new \DateTime());
        $this->taskOutputFactory->create($taskWithOutput, [
            TaskOutputFactory::KEY_OUTPUT => 'non-relevant output',
        ]);

        $this->httpClientService->appendFixtures($httpFixtures);

        $returnValue = $this->linkIntegrityTaskPreProcessor->process($selectedTask);

        $selectedTaskOutput = $selectedTask->getOutput();

        $this->assertFalse($returnValue);
        $this->assertEquals(Task::STATE_QUEUED, $selectedTask->getState()->getName());
        $this->assertEmpty($selectedTaskOutput);
    }

    /**
     * @return array
     */
    public function processWebResourceFailureDataProvider()
    {
        $movedPermanentlyRedirectResponse = new Response(301, ['location' => 'http://example.com/1']);

        return [
            'http 404 getting web resource' => [
                'httpFixtures' => [
                    new Response(404),
                ],
            ],
            'curl timeout getting web resource' => [
                'httpFixtures' => [
                    ConnectExceptionFactory::create(28, 'operation timed out'),
                ],
            ],
            'too many redirects getting web resource' => [
                'httpFixtures' => [
                    $movedPermanentlyRedirectResponse,
                    $movedPermanentlyRedirectResponse,
                    $movedPermanentlyRedirectResponse,
                    $movedPermanentlyRedirectResponse,
                    $movedPermanentlyRedirectResponse,
                    $movedPermanentlyRedirectResponse,
                ],
            ],
            'web resource not web page' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/plain']),
                ],
            ],
        ];
    }

    /**
     * @dataProvider processSingleLinkWithExistingLinkIntegrityErrorDataProvider
     *
     * @param int $existingLinkIntegrityErrorState
     * @param string $existingLinkIntegrityErrorType
     */
    public function testProcessSingleLinkWithExistingLinkIntegrityError(
        $existingLinkIntegrityErrorState,
        $existingLinkIntegrityErrorType
    ) {
        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => [
                TaskTypeService::LINK_INTEGRITY_TYPE,
            ],
        ]);

        $tasks = $job->getTasks();

        /* @var Task $selectedTask */
        $selectedTask = $tasks->get(0);
        $selectedTaskOutput = $selectedTask->getOutput();
        $this->assertEmpty($selectedTaskOutput);

        $taskWithOutput = $tasks->get(1);
        $this->taskFactory->setEndDateTime($taskWithOutput, new \DateTime());
        $this->taskOutputFactory->create($taskWithOutput, [
            TaskOutputFactory::KEY_OUTPUT => json_encode([
                [
                    'context' => '<a href="http://example.com/1">One</a>',
                    'state' => $existingLinkIntegrityErrorState,
                    'type' => $existingLinkIntegrityErrorType,
                    'url' => 'http://example.com/1'
                ],
            ]),
        ]);

        $this->httpClientService->appendFixtures([
            new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('single-link')),
        ]);

        $returnValue = $this->linkIntegrityTaskPreProcessor->process($selectedTask);

        $selectedTaskOutput = $selectedTask->getOutput();

        $this->assertFalse($returnValue);
        $this->assertEquals(Task::STATE_QUEUED, $selectedTask->getState()->getName());
        $this->assertEmpty($selectedTaskOutput);
    }

    /**
     * @return array
     */
    public function processSingleLinkWithExistingLinkIntegrityErrorDataProvider()
    {
        return [
            'curl error' => [
                'existingLinkIntegrityErrorState' => 6,
                'existingLinkIntegrityErrorType' => 'curl',
            ],
            'http 3XX error' => [
                'existingLinkIntegrityErrorState' => 301,
                'existingLinkIntegrityErrorType' => 'http',
            ],
            'http 4XX error' => [
                'existingLinkIntegrityErrorState' => 404,
                'existingLinkIntegrityErrorType' => 'http',
            ],
            'http 5XX error' => [
                'existingLinkIntegrityErrorState' => 500,
                'existingLinkIntegrityErrorType' => 'http',
            ],
        ];
    }

    /**
     * @dataProvider processCreatesPartialOutputDataProvider
     *
     * @param array $jobParameters
     * @param string $taskOutput
     * @param string $expectedTaskOutput
     * @param array $expectedTaskParameters
     */
    public function testProcessCreatesPartialOutput(
        $jobParameters,
        $taskOutput,
        $expectedTaskOutput,
        $expectedTaskParameters
    ) {
        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => [
                TaskTypeService::LINK_INTEGRITY_TYPE,
            ],
            JobFactory::KEY_PARAMETERS => $jobParameters,
        ]);

        $tasks = $job->getTasks();

        /* @var Task $selectedTask */
        $selectedTask = $tasks->get(0);
        $selectedTaskOutput = $selectedTask->getOutput();

        $this->assertEmpty($selectedTaskOutput);

        $taskWithOutput = $tasks->get(1);
        $this->taskFactory->setEndDateTime($taskWithOutput, new \DateTime());
        $this->taskOutputFactory->create($taskWithOutput, [
            TaskOutputFactory::KEY_OUTPUT => $taskOutput,
        ]);

        $this->httpClientService->appendFixtures([
            new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('three-links')),
        ]);

        $returnValue = $this->linkIntegrityTaskPreProcessor->process($selectedTask);

        $this->assertFalse($returnValue);
        $this->assertEquals(Task::STATE_QUEUED, $selectedTask->getState()->getName());

        $selectedTaskOutput = $selectedTask->getOutput();

        $this->assertNotEmpty($selectedTaskOutput);
        $this->assertEquals($expectedTaskOutput, $selectedTaskOutput->getOutput());
        $this->assertEquals($expectedTaskParameters, $selectedTask->getParameters()->getAsArray());
    }

    /**
     * @return array
     */
    public function processCreatesPartialOutputDataProvider()
    {
        return [
            'matching results on one url of three' => [
                'jobParameters' => [],
                'taskOutput' => json_encode([
                    [
                        'context' => '<a href="http://example.com/1">Foo One</a>',
                        'state' => 200,
                        'type' => 'http',
                        'url' => 'http://example.com/1',
                    ],
                ]),
                'expectedTaskOutput' => json_encode([
                    [
                        'context' => '<a href="http://example.com/1">One</a>',
                        'state' => 200,
                        'type' => 'http',
                        'url' => 'http://example.com/1',
                    ],
                ]),
                'expectedTaskParameters' => [
                    'excluded-urls' => [
                        'http://example.com/1',
                    ],
                ],
            ],
            'matching results on two urls of three' => [
                'jobParameters' => [
                    'param-name' => 'param-value',
                ],
                'taskOutput' => json_encode([
                    [
                        'context' => '<a href="http://example.com/1">Foo One</a>',
                        'state' => 200,
                        'type' => 'http',
                        'url' => 'http://example.com/1',
                    ],
                    [
                        'context' => '<a href="http://example.com/3">Foo Three</a>',
                        'state' => 200,
                        'type' => 'http',
                        'url' => 'http://example.com/3',
                    ],
                ]),
                'expectedTaskOutputValues' => json_encode([
                    [
                        'context' => '<a href="http://example.com/1">One</a>',
                        'state' => 200,
                        'type' => 'http',
                        'url' => 'http://example.com/1',
                    ],
                    [
                        'context' => '<a href="http://example.com/3">Three</a>',
                        'state' => 200,
                        'type' => 'http',
                        'url' => 'http://example.com/3',
                    ],
                ]),
                'expectedTaskParameters' => [
                    'param-name' => 'param-value',
                    'excluded-urls' => [
                        'http://example.com/1',
                        'http://example.com/3',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider processDataProvider
     *
     * @param array $httpFixtures
     * @param array $taskOutputValuesCollection
     * @param bool $expectedTaskPreProcessorReturnValue
     * @param string $expectedTaskStateName
     * @param array $expectedTaskOutputValues
     */
    public function testProcess(
        $httpFixtures,
        $taskOutputValuesCollection,
        $expectedTaskPreProcessorReturnValue,
        $expectedTaskStateName,
        $expectedTaskOutputValues
    ) {
        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => [
                TaskTypeService::LINK_INTEGRITY_TYPE,
            ],
        ]);

        $tasks = $job->getTasks();

        /* @var Task $selectedTask */
        $selectedTask = $tasks->get(0);
        $taskOutput = $selectedTask->getOutput();
        $this->assertEmpty($taskOutput);

        foreach ($tasks as $taskIndex => $task) {
            if (!empty($taskOutputValuesCollection[$taskIndex])) {
                $this->taskFactory->setEndDateTime($task, new \DateTime());
                $this->taskOutputFactory->create($task, $taskOutputValuesCollection[$taskIndex]);
            }
        }

        $this->httpClientService->appendFixtures($httpFixtures);

        $returnValue = $this->linkIntegrityTaskPreProcessor->process($selectedTask);

        $this->assertEquals(
            $expectedTaskPreProcessorReturnValue,
            $returnValue
        );

        $this->assertEquals($expectedTaskStateName, $selectedTask->getState()->getName());

        $taskOutput = $selectedTask->getOutput();

        if (empty($expectedTaskOutputValues)) {
            $this->assertEmpty($taskOutput);
        } else {
            $this->assertNotEmpty($taskOutput);

            $this->assertEquals(
                $expectedTaskOutputValues['output'],
                $taskOutput->getOutput()
            );

            $this->assertEquals(
                $expectedTaskOutputValues['errorCount'],
                $taskOutput->getErrorCount()
            );
        }
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            'all empty outputs' => [
                'httpFixtures' => [],
                'taskOutputValuesCollection' => [
                    null,
                    [],
                    [],
                ],
                'expectedTaskPreProcessorReturnValue' => false,
                'expectedTaskStateName' => 'task-queued',
                'expectedTaskOutputValues' => null,
            ],
            'no links in task web resource' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('minimal')),
                ],
                'taskOutputValuesCollection' => [
                    [],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'non-relevant content',
                    ],
                    [],
                ],
                'expectedTaskPreProcessorReturnValue' => true,
                'expectedTaskStateName' => Task::STATE_COMPLETED,
                'expectedTaskOutputValues' => [
                    'output' => null,
                    'errorCount' => 0,
                ],
            ],
            'no matching results on url' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('single-link')),
                ],
                'taskOutputValuesCollection' => [
                    [],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'invalid output',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            [
                                'context' => '<a href="http://example.com/2">Two</a>',
                                'state' => 200,
                                'type' => 'http',
                                'url' => 'http://example.com/2'
                            ],
                        ]),
                    ],
                ],
                'expectedTaskPreProcessorReturnValue' => false,
                'expectedTaskStateName' => 'task-queued',
                'expectedTaskOutputValues' => null,
            ],
            'matching results on one url of one' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('single-link')),
                ],
                'taskOutputValuesCollection' => [
                    [],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'invalid output',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            [
                                'not an object',
                            ],
                            [
                                'context' => '<a href="http://example.com/1">Foo One</a>',
                                'state' => 200,
                                'type' => 'http',
                                'url' => 'http://example.com/1',
                            ],
                        ]),
                    ],
                ],
                'expectedTaskPreProcessorReturnValue' => true,
                'expectedTaskStateName' => Task::STATE_COMPLETED,
                'expectedTaskOutputValues' => [
                    'output' => json_encode([
                        [
                            'context' => '<a href="http://example.com/1">One</a>',
                            'state' => 200,
                            'type' => 'http',
                            'url' => 'http://example.com/1',
                        ],
                    ]),
                    'errorCount' => 0,
                ],
            ],
        ];
    }

    public function testProcessSettingHttpProperties()
    {
        $httpClientService = self::$container->get(HttpClientService::class);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => [
                TaskTypeService::LINK_INTEGRITY_TYPE,
            ],
            JobFactory::KEY_PARAMETERS => [
                'cookies' => [
                    [
                        'domain' => '.example.com',
                        'name' => 'foo',
                        'value' => 'bar'
                    ]
                ]
            ],
        ]);

        $tasks = $job->getTasks();

        /* @var Task $selectedTask */
        $selectedTask = $tasks->get(0);

        $taskWithOutput = $tasks->get(1);
        $this->taskFactory->setEndDateTime($taskWithOutput, new \DateTime());
        $this->taskOutputFactory->create($taskWithOutput, [
            TaskOutputFactory::KEY_OUTPUT => 'foo',
        ]);

        $this->httpClientService->appendFixtures([
            new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('minimal')),
        ]);

        $this->linkIntegrityTaskPreProcessor->process($selectedTask);

        $httpHistory = $httpClientService->getHistory();

        foreach ($httpHistory as $httpTransaction) {
            /* @var RequestInterface $request */
            $request = $httpTransaction['request'];

            $this->assertEquals('foo=bar', $request->getHeaderLine('cookie'));
        }

        $this->assertEquals(
            LinkIntegrityTaskPreProcessor::HTTP_USER_AGENT,
            $httpHistory->getLastRequest()->getHeaderLine('user-agent')
        );
    }
}
