<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskOutputJoiner;

use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Services\TaskOutputJoiner\LinkIntegrityTaskOutputJoiner;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\InternetMediaTypeFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

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

        $this->jobFactory = new JobFactory($this->container);
        $this->linkIntegrityTaskOutputJoiner = $this->container->get(
            'simplytestable.services.taskputoutjoiner.linkintegrity'
        );
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

            if (isset($outputValues['warningCount'])) {
                $output->setWarningCount($outputValues['warningCount']);
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

//    public function testJoinOnComplete()
//    {
//        $userService = $this->container->get('simplytestable.services.userservice');
//        $this->setUser($userService->getPublicUser());
//
//        $job = $this->jobFactory->createResolveAndPrepare([
//            JobFactory::KEY_TEST_TYPES => ['link integrity'],
//        ]);
//
//        $this->queueHttpFixtures([
//            HttpFixtureFactory::createSuccessResponse(
//                'text/html',
//                '<!DOCTYPE html>
//                       <html lang="en">
//                           <body>
//                               <a href="http://example.com/three">Another Example Three</a>
//                               <a href="http://example.com/one">Another Example One</a>
//                               <a href="http://example.com/two">Another Example Two</a>
//                               <a href="http://example.com/four">Example Four</a>
//                           </body>
//                       </html>'
//            ),
//            HttpFixtureFactory::createSuccessResponse(
//                'application/json',
//                json_encode([])
//            )
//        ]);
//
//        $tasks = $job->getTasks();
//
//        $now = new \DateTime();
//
//        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
//            'end_date_time' => $now->format('Y-m-d H:i:s'),
//            'output' => json_encode(array(
//                array(
//                    'context' => '<a href="http://example.com/one">Example One</a>',
//                    'state' => 404,
//                    'type' => 'http',
//                    'url' => 'http://example.com/one'
//                ),
//                array(
//                    'context' => '<a href="http://example.com/two">Example Two</a>',
//                    'state' => 200,
//                    'type' => 'http',
//                    'url' => 'http://example.com/two'
//                ),
//                array(
//                    'context' => '<a href="http://example.com/three">Example Three</a>',
//                    'state' => 200,
//                    'type' => 'http',
//                    'url' => 'http://example.com/three'
//                )
//            )),
//            'contentType' => 'application/json',
//            'state' => 'completed',
//            'errorCount' => 0,
//            'warningCount' => 0
//        ], [
//            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[0]->getType(),
//            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[0]->getUrl(),
//            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[0]->getParametersHash(),
//        ]);
//
//        $taskController = new TaskController();
//        $taskController->setContainer($this->container);
//
//        $this->container->get('request_stack')->push($taskCompleteRequest);
//        $this->container->get('simplytestable.services.request.factory.task.complete')->init($taskCompleteRequest);
//
//        $taskController->completeAction();
//
//        $taskAssignCollectionCommand = $this->container->get('simplytestable.command.task.assigncollection');
//        $taskAssignCollectionCommand->run(new ArrayInput([
//            'ids' => $tasks[1]->getId()
//        ]), new BufferedOutput());
//
//        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
//            'end_date_time' => $now->format('Y-m-d H:i:s'),
//            'output' => json_encode(array(
//                array(
//                    'context' => '<a href="http://example.com/one">Example One</a>',
//                    'state' => 404,
//                    'type' => 'http',
//                    'url' => 'http://example.com/one'
//                ),
//                array(
//                    'context' => '<a href="http://example.com/four">Example Four</a>',
//                    'state' => 404,
//                    'type' => 'http',
//                    'url' => 'http://example.com/four'
//                )
//            )),
//            'contentType' => 'application/json',
//            'state' => 'completed',
//            'errorCount' => 0,
//            'warningCount' => 0
//        ], [
//            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[1]->getType(),
//            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[1]->getUrl(),
//            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[1]->getParametersHash(),
//        ]);
//
//        $taskController = new TaskController();
//        $taskController->setContainer($this->container);
//
//        $this->container->get('request_stack')->push($taskCompleteRequest);
//        $this->container->get('simplytestable.services.request.factory.task.complete')->init($taskCompleteRequest);
//
//        $taskController->completeAction();
//
//        $this->assertEquals(2, $tasks[1]->getOutput()->getErrorCount());
//    }
//
//    public function testJoinGetsCorrectErrorCount()
//    {
//        $userService = $this->container->get('simplytestable.services.userservice');
//        $this->setUser($userService->getPublicUser());
//
//        $job = $this->jobFactory->createResolveAndPrepare([
//            JobFactory::KEY_TEST_TYPES => ['link integrity'],
//        ]);
//
//        $this->queueHttpFixtures([
//            HttpFixtureFactory::createSuccessResponse(
//                'text/html',
//                '<!DOCTYPE html>
//                       <html lang="en">
//                           <body>
//                               <a href="http://example.com/three">Another Example Three</a>
//                               <a href="http://example.com/one">Another Example One</a>
//                               <a href="http://example.com/two">Another Example Two</a>
//                               <a href="http://example.com/four">Example Four</a>
//                           </body>
//                       </html>'
//            ),
//            HttpFixtureFactory::createSuccessResponse(
//                'application/json',
//                json_encode([])
//            )
//        ]);
//
//        $tasks = $job->getTasks();
//
//        $now = new \DateTime();
//
//        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
//            'end_date_time' => $now->format('Y-m-d H:i:s'),
//            'output' => json_encode(array(
//                array(
//                    'context' => '<a href="http://example.com/one">Example One</a>',
//                    'state' => 404,
//                    'type' => 'http',
//                    'url' => 'http://example.com/one'
//                ),
//                array(
//                    'context' => '<a href="http://example.com/two">Example Two</a>',
//                    'state' => 200,
//                    'type' => 'http',
//                    'url' => 'http://example.com/two'
//                ),
//                array(
//                    'context' => '<a href="http://example.com/three">Example Three</a>',
//                    'state' => 204,
//                    'type' => 'http',
//                    'url' => 'http://example.com/three'
//                )
//            )),
//            'contentType' => 'application/json',
//            'state' => 'completed',
//            'errorCount' => 1,
//            'warningCount' => 0
//        ], [
//            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[0]->getType(),
//            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[0]->getUrl(),
//            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[0]->getParametersHash(),
//        ]);
//
//        $taskController = new TaskController();
//        $taskController->setContainer($this->container);
//
//        $this->container->get('request_stack')->push($taskCompleteRequest);
//        $this->container->get('simplytestable.services.request.factory.task.complete')->init($taskCompleteRequest);
//
//        $taskController->completeAction();
//
//        $taskAssignCollectionCommand = $this->container->get('simplytestable.command.task.assigncollection');
//        $taskAssignCollectionCommand->run(new ArrayInput([
//            'ids' => $tasks[1]->getId()
//        ]), new BufferedOutput());
//
//        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
//            'end_date_time' => $now->format('Y-m-d H:i:s'),
//            'output' => json_encode(array(
//                array(
//                    'context' => '<a href="http://example.com/one">Example One</a>',
//                    'state' => 404,
//                    'type' => 'http',
//                    'url' => 'http://example.com/one'
//                ),
//                array(
//                    'context' => '<a href="http://example.com/four">Example Four</a>',
//                    'state' => 404,
//                    'type' => 'http',
//                    'url' => 'http://example.com/four'
//                )
//            )),
//            'contentType' => 'application/json',
//            'state' => 'completed',
//            'errorCount' => 1,
//            'warningCount' => 0
//        ], [
//            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[1]->getType(),
//            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[1]->getUrl(),
//            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[1]->getParametersHash(),
//        ]);
//
//        $taskController = new TaskController();
//        $taskController->setContainer($this->container);
//
//        $this->container->get('request_stack')->push($taskCompleteRequest);
//        $this->container->get('simplytestable.services.request.factory.task.complete')->init($taskCompleteRequest);
//
//        $taskController->completeAction();
//
//        $this->assertEquals(2, $tasks[1]->getOutput()->getErrorCount());
//    }
}
