<?php

namespace Tests\ApiBundle\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\MockFactory;
use Tests\ApiBundle\Factory\TaskControllerCompleteActionRequestFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use SimplyTestable\ApiBundle\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;

/**
 * @group Controller/Job/JobController
 */
class JobControllerTasksActionTest extends AbstractJobControllerTest
{
    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => self::$container->get('router')->generate('job_job_tasks', [
                'test_id' => $job->getId(),
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
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
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[0]->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[0]->getParametersHash(),
        ]);

        $taskController = self::$container->get(TaskController::class);
        self::$container->get('request_stack')->push($taskCompleteRequest);
        self::$container->get(CompleteRequestFactory::class)->init($taskCompleteRequest);

        $this->callTaskControllerCompleteAction($taskController);

        $tasksActionResponse = $this->callTasksAction(new Request(), $job);

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
                CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
                CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
            ]);

            self::$container->get('request_stack')->push($taskCompleteRequest);
            self::$container->get(CompleteRequestFactory::class)->init($taskCompleteRequest);

            $this->callTaskControllerCompleteAction($taskController);
        }

        $tasksActionResponse = $this->callTasksAction(new Request(), $job);

        $tasksResponseObject = json_decode($tasksActionResponse->getContent());

        foreach ($tasksResponseObject as $taskResponse) {
            $this->assertTrue(isset($taskResponse->output));
        }
    }

    /**
     * @dataProvider requestTaskIdsDataProvider
     *
     * @param string $requestTaskIdIndices
     * @param array $expectedTaskDataCollection
     */
    public function testWithRequestTaskIds($requestTaskIdIndices, $expectedTaskDataCollection)
    {
        $userService = self::$container->get(UserService::class);
        $this->setUser($userService->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare();

        $requestData = [];
        if (!is_null($requestTaskIdIndices)) {
            $requestData['taskIds'] = $this->createRequestTaskIdsFromRequestTaskIndices($job, $requestTaskIdIndices);
        }

        $tasksActionResponse = $this->callTasksAction(
            new Request([], $requestData),
            $job
        );

        $tasksResponseData = json_decode($tasksActionResponse->getContent(), true);

        $this->assertCount(count($expectedTaskDataCollection), $tasksResponseData);

        foreach ($expectedTaskDataCollection as $taskIndex => $expectedTaskData) {
            $comparator = $tasksResponseData[$taskIndex];
            unset($comparator['id']);

            $this->assertEquals($expectedTaskData, $comparator);
        }
    }

    /**
     * @return array
     */
    public function requestTaskIdsDataProvider()
    {
        return [
            'all' => [
                'requestTaskIdIndices' => null,
                'expectedTaskDataCollection' => [
                    [
                        'url' => 'http://example.com/one',
                        'state' => 'queued',
                        'worker' => '',
                        'type' => 'HTML validation',
                    ],
                    [
                        'url' => 'http://example.com/bar%20foo',
                        'state' => 'queued',
                        'worker' => '',
                        'type' => 'HTML validation',
                    ],
                    [
                        'url' => 'http://example.com/foo bar',
                        'state' => 'queued',
                        'worker' => '',
                        'type' => 'HTML validation',
                    ],
                ],
            ],
            'first only' => [
                'requestTaskIdIndices' => '0',
                'expectedTaskData' => [
                    [
                        'url' => 'http://example.com/one',
                        'state' => 'queued',
                        'worker' => '',
                        'type' => 'HTML validation',
                    ],
                ],
            ],
            'third only' => [
                'requestTaskIdIndices' => '2',
                'expectedTaskData' => [
                    [
                        'url' => 'http://example.com/foo bar',
                        'state' => 'queued',
                        'worker' => '',
                        'type' => 'HTML validation',
                    ],
                ],
            ],
            'first and third' => [
                'requestTaskIdIndices' => '0,2',
                'expectedTaskDataCollection' => [
                    [
                        'url' => 'http://example.com/one',
                        'state' => 'queued',
                        'worker' => '',
                        'type' => 'HTML validation',
                    ],
                    [
                        'url' => 'http://example.com/foo bar',
                        'state' => 'queued',
                        'worker' => '',
                        'type' => 'HTML validation',
                    ],
                ],
            ],
            'second and third with range' => [
                'requestTaskIdIndices' => '1:2',
                'expectedTaskDataCollection' => [
                    [
                        'url' => 'http://example.com/bar%20foo',
                        'state' => 'queued',
                        'worker' => '',
                        'type' => 'HTML validation',
                    ],
                    [
                        'url' => 'http://example.com/foo bar',
                        'state' => 'queued',
                        'worker' => '',
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

    /**
     * @param Request $request
     * @param Job $job
     *
     * @return JsonResponse
     */
    private function callTasksAction(Request $request, Job $job)
    {
        return $this->jobController->tasksAction(
            self::$container->get(TaskService::class),
            $request,
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
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
