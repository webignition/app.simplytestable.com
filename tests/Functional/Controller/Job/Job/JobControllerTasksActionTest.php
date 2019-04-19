<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Controller\Job\Job;

use App\Controller\TaskController;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Services\CrawlJobContainerService;
use App\Services\JobPreparationService;
use App\Services\JobService;
use App\Services\Request\Factory\Task\CompleteRequestFactory;
use App\Services\StateService;
use App\Services\TaskService;
use App\Services\UserService;
use App\Tests\Services\JobFactory;
use App\Tests\Services\TaskFactory;
use App\Tests\Services\TaskOutputFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Tests\Factory\MockFactory;
use App\Tests\Factory\TaskControllerCompleteActionRequestFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use App\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;
use webignition\InternetMediaType\InternetMediaType;

/**
 * @group Controller/Job/JobController
 */
class JobControllerTasksActionTest extends AbstractJobControllerTest
{
    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => self::$container->get('router')->generate('job_job_tasks', [
                'test_id' => $job->getId(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNoOutputForIncompleteTasksWithPartialOutput()
    {
        $userService = self::$container->get(UserService::class);
        $this->setUser($userService->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => ['link integrity'],
        ]);

        $tasks = $job->getTasks();

        $now = new \DateTime();

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode(array(
                array(
                    'context' => '<a href="http://example.com/one">Example One</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/one'
                ),
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 1,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[0]->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode($tasks[0]->getUrl()),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[0]->getParametersHash(),
        ]);

        $taskController = self::$container->get(TaskController::class);
        self::$container->get('request_stack')->push($taskCompleteRequest);
        self::$container->get(CompleteRequestFactory::class)->init($taskCompleteRequest);

        $this->callTaskControllerCompleteAction($taskController);

        $tasksActionResponse = $this->callTasksAction(new Request(), $job->getId());

        $tasksResponseData = json_decode($tasksActionResponse->getContent(), true);

        foreach ($tasksResponseData as $taskData) {
            if ($taskData['id'] == $tasks[0]->getId()) {
                $this->assertTrue(isset($taskData['output']));
            } else {
                $this->assertFalse(isset($taskData['output']));
            }
        }
    }

    public function testFailedNoRetryAvailableTaskOutputIsReturned()
    {
        $userService = self::$container->get(UserService::class);
        $this->setUser($userService->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare();

        $taskController = self::$container->get(TaskController::class);

        foreach ($job->getTasks() as $task) {
            $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
                'end_date_time' => '2012-03-08 17:03:00',
                'output' => '{"messages":[]}',
                'contentType' => 'application/json',
                'state' => 'task-failed-no-retry-available',
                'errorCount' => 1,
                'warningCount' => 0
            ], [
                CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
                CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode($task->getUrl()),
                CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
            ]);

            self::$container->get('request_stack')->push($taskCompleteRequest);
            self::$container->get(CompleteRequestFactory::class)->init($taskCompleteRequest);

            $this->callTaskControllerCompleteAction($taskController);
        }

        $tasksActionResponse = $this->callTasksAction(new Request(), $job->getId());

        $tasksResponseObject = json_decode($tasksActionResponse->getContent());

        foreach ($tasksResponseObject as $taskResponse) {
            $this->assertTrue(isset($taskResponse->output));
        }
    }

    /**
     * @dataProvider successDataProvider
     */
    public function testSuccess(
        array $taskValuesCollection,
        string $requestTaskIdIndices,
        array $expectedTaskDataCollection
    ) {
        $userService = self::$container->get(UserService::class);
        $this->setUser($userService->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TASKS => $taskValuesCollection,
        ]);

        $requestData = [];
        if ('' !== $requestTaskIdIndices) {
            $requestData['taskIds'] = $this->createRequestTaskIdsFromRequestTaskIndices($job, $requestTaskIdIndices);
        }

        $tasksActionResponse = $this->callTasksAction(
            new Request([], $requestData),
            $job->getId()
        );

        $tasksResponseData = json_decode($tasksActionResponse->getContent(), true);
        $this->assertCount(count($expectedTaskDataCollection), $tasksResponseData);

        foreach ($expectedTaskDataCollection as $taskIndex => $expectedTaskData) {
            $comparator = $tasksResponseData[$taskIndex];
            unset($comparator['id']);

            $this->assertEquals($expectedTaskData, $comparator);
        }
    }

    public function successDataProvider(): array
    {
        return [
            'all: cancelled, completed, failed-retry-available; with output' => [
                'taskValuesCollection' => [
                    [
                        TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                        TaskFactory::KEY_OUTPUT => [
                            TaskOutputFactory::KEY_OUTPUT => 'cancelled output',
                            TaskOutputFactory::KEY_CONTENT_TYPE => new InternetMediaType('text', 'plain'),
                            TaskOutputFactory::KEY_ERROR_COUNT => 1,
                            TaskOutputFactory::KEY_WARNING_COUNT => 2,
                        ],
                    ],
                    [
                        TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                        TaskFactory::KEY_OUTPUT => [
                            TaskOutputFactory::KEY_OUTPUT => 'completed output',
                            TaskOutputFactory::KEY_CONTENT_TYPE => new InternetMediaType('text', 'plain'),
                            TaskOutputFactory::KEY_ERROR_COUNT => 3,
                            TaskOutputFactory::KEY_WARNING_COUNT => 4,
                        ],
                    ],
                    [
                        TaskFactory::KEY_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                        TaskFactory::KEY_OUTPUT => [
                            TaskOutputFactory::KEY_OUTPUT => 'failed retry available output',
                            TaskOutputFactory::KEY_CONTENT_TYPE => new InternetMediaType('text', 'plain'),
                            TaskOutputFactory::KEY_ERROR_COUNT => 5,
                            TaskOutputFactory::KEY_WARNING_COUNT => 6,
                        ],
                    ],
                ],
                'requestTaskIdIndices' => '',
                'expectedTaskDataCollection' => [
                    [
                        'url' => 'http://example.com/one',
                        'state' => 'cancelled',
                        'type' => 'HTML validation',
                        'output' => [
                            'output' => 'cancelled output',
                            'content_type' => 'text/plain',
                            'error_count' => 1,
                            'warning_count' => 2,
                        ],
                    ],
                    [
                        'url' => 'http://example.com/bar%20foo',
                        'state' => 'completed',
                        'type' => 'HTML validation',
                        'output' => [
                            'output' => 'completed output',
                            'content_type' => 'text/plain',
                            'error_count' => 3,
                            'warning_count' => 4,
                        ],
                    ],
                    [
                        'url' => 'http://example.com/foo bar',
                        'state' => 'failed-retry-available',
                        'type' => 'HTML validation',
                        'output' => [
                            'output' => 'failed retry available output',
                            'content_type' => 'text/plain',
                            'error_count' => 5,
                            'warning_count' => 6,
                        ],
                    ],
                ],
            ],
            'all: failed-no-retry-available, failed-retry-limit-reached, skipped' => [
                'taskValuesCollection' => [
                    [
                        TaskFactory::KEY_STATE => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                    ],
                    [
                        TaskFactory::KEY_STATE => Task::STATE_FAILED_RETRY_LIMIT_REACHED,
                    ],
                    [
                        TaskFactory::KEY_STATE => Task::STATE_SKIPPED,
                    ],
                ],
                'requestTaskIdIndices' => '',
                'expectedTaskDataCollection' => [
                    [
                        'url' => 'http://example.com/one',
                        'state' => 'failed-no-retry-available',
                        'type' => 'HTML validation',
                    ],
                    [
                        'url' => 'http://example.com/bar%20foo',
                        'state' => 'failed-retry-limit-reached',
                        'type' => 'HTML validation',
                    ],
                    [
                        'url' => 'http://example.com/foo bar',
                        'state' => 'skipped',
                        'type' => 'HTML validation',
                    ],
                ],
            ],
            'first only, expired, with output' => [
                'taskValuesCollection' => [
                    [
                        TaskFactory::KEY_STATE => Task::STATE_EXPIRED,
                        TaskFactory::KEY_OUTPUT => [
                            TaskOutputFactory::KEY_OUTPUT => null,
                            TaskOutputFactory::KEY_CONTENT_TYPE => null,
                            TaskOutputFactory::KEY_ERROR_COUNT => 1,
                            TaskOutputFactory::KEY_WARNING_COUNT => 2,
                        ],
                    ],
                ],
                'requestTaskIdIndices' => '0',
                'expectedTaskDataCollection' => [
                    [
                        'url' => 'http://example.com/one',
                        'state' => 'expired',
                        'type' => 'HTML validation',
                        'output' => [
                            'output' => null,
                            'content_type' => null,
                            'error_count' => 1,
                            'warning_count' => 2,
                        ],
                    ],
                ],
            ],
            'first only' => [
                'taskValuesCollection' => [],
                'requestTaskIdIndices' => '0',
                'expectedTaskData' => [
                    [
                        'url' => 'http://example.com/one',
                        'state' => 'queued',
                        'type' => 'HTML validation',
                    ],
                ],
            ],
            'third only' => [
                'taskValuesCollection' => [],
                'requestTaskIdIndices' => '2',
                'expectedTaskData' => [
                    [
                        'url' => 'http://example.com/foo bar',
                        'state' => 'queued',
                        'type' => 'HTML validation',
                    ],
                ],
            ],
            'first and third' => [
                'taskValuesCollection' => [],
                'requestTaskIdIndices' => '0,2',
                'expectedTaskDataCollection' => [
                    [
                        'url' => 'http://example.com/one',
                        'state' => 'queued',
                        'type' => 'HTML validation',
                    ],
                    [
                        'url' => 'http://example.com/foo bar',
                        'state' => 'queued',
                        'type' => 'HTML validation',
                    ],
                ],
            ],
            'second and third with range' => [
                'taskValuesCollection' => [],
                'requestTaskIdIndices' => '1:2',
                'expectedTaskDataCollection' => [
                    [
                        'url' => 'http://example.com/bar%20foo',
                        'state' => 'queued',
                        'type' => 'HTML validation',
                    ],
                    [
                        'url' => 'http://example.com/foo bar',
                        'state' => 'queued',
                        'type' => 'HTML validation',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param Job $job
     * @param $requestTaskIndices
     *
     * @return string
     */
    private function createRequestTaskIdsFromRequestTaskIndices(Job $job, $requestTaskIndices)
    {
        $hasCommaSeparatedList = strpos($requestTaskIndices, ',') !== false;
        $hasColonSeparatedRange = strpos($requestTaskIndices, ':') !== false;

        $taskIds = [];
        foreach ($job->getTasks() as $taskIndex => $task) {
            $taskIds[] = $task->getId();
        }

        if ($hasCommaSeparatedList) {
            $requestTaskIds = [];
            $indices = explode(',', $requestTaskIndices);

            foreach ($taskIds as $taskIndex => $taskId) {
                /* @var Task $task */
                if (in_array($taskIndex, $indices)) {
                    $requestTaskIds[] = $taskId;
                }
            }

            return implode(',', $requestTaskIds);
        }

        if ($hasColonSeparatedRange) {
            $indexRanges = explode(':', $requestTaskIndices);
            $taskIdRanges = [];

            foreach ($indexRanges as $indexRange) {
                $taskIdRanges[] = $taskIds[$indexRange];
            }

            return implode(':', $taskIdRanges);
        }

        return (string)$taskIds[$requestTaskIndices];
    }

    private function callTasksAction(Request $request, int $jobId): JsonResponse
    {
        return $this->jobController->tasksAction(
            self::$container->get(TaskService::class),
            $request,
            $jobId
        );
    }

    private function callTaskControllerCompleteAction(TaskController $taskController)
    {
        return $taskController->completeAction(
            MockFactory::createApplicationStateService(),
            self::$container->get(ResqueQueueService::class),
            self::$container->get(CompleteRequestFactory::class),
            self::$container->get(TaskService::class),
            self::$container->get(JobService::class),
            self::$container->get(JobPreparationService::class),
            self::$container->get(CrawlJobContainerService::class),
            self::$container->get(TaskOutputJoinerFactory::class),
            self::$container->get(TaskPostProcessorFactory::class),
            self::$container->get(StateService::class),
            MockFactory::createTaskTypeDomainsToIgnoreService()
        );
    }
}
