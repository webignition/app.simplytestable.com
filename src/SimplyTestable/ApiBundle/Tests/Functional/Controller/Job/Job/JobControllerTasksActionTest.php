<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class JobControllerTasksActionTest extends AbstractJobControllerTest
{
    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_job_tasks', [
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
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

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

        $taskController = new TaskController();
        $taskController->setContainer($this->container);
        $this->container->get('request_stack')->push($taskCompleteRequest);
        $this->container->get('simplytestable.services.request.factory.task.complete')->init($taskCompleteRequest);

        $taskController->completeAction();

        $tasksActionResponse = $this->jobController->tasksAction(
            new Request(),
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

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
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare();

        $taskController = new TaskController();
        $taskController->setContainer($this->container);

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

            $this->container->get('request_stack')->push($taskCompleteRequest);
            $this->container->get('simplytestable.services.request.factory.task.complete')->init($taskCompleteRequest);

            $taskController->completeAction();
        }

        $tasksActionResponse = $this->jobController->tasksAction(
            new Request(),
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $tasksResponseObject = json_decode($tasksActionResponse->getContent());

        foreach ($tasksResponseObject as $taskResponse) {
            $this->assertTrue(isset($taskResponse->output));
        }
    }

    /**
     * @dataProvider accessDataProvider
     *
     * @param string $owner
     * @param string $requester
     * @param bool $callSetPublic
     * @param int $expectedResponseStatusCode
     */
    public function testAccess($owner, $requester, $callSetPublic, $expectedResponseStatusCode)
    {
        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $ownerUser = $users[$owner];
        $requesterUser = $users[$requester];

        $this->getUserService()->setUser($ownerUser);
        $canonicalUrl = 'http://example.com/';

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_USER => $ownerUser,
        ]);

        if ($callSetPublic) {
            $this->jobController->setPublicAction($canonicalUrl, $job->getId());
        }

        $this->getUserService()->setUser($requesterUser);

        $response = $this->jobController->tasksAction(new Request(), $canonicalUrl, $job->getId());

        $this->assertEquals($expectedResponseStatusCode, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function accessDataProvider()
    {
        return [
            'public owner, public requester' => [
                'owner' => 'public',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedStatusCode' => 200,
            ],
            'public owner, private requester' => [
                'owner' => 'public',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedStatusCode' => 200,
            ],
            'private owner, private requester' => [
                'owner' => 'private',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedStatusCode' => 200,
            ],
            'private owner, public requester' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedStatusCode' => 403,
            ],
            'private owner, public requester, public test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => true,
                'expectedStatusCode' => 200,
            ],
        ];
    }

    /**
     * @dataProvider requestTaskIdsDataProvider
     *
     * @param string $requestTaskIdIndices
     * @param array $expectedTaskDataCollection
     */
    public function testWithRequestTaskIds($requestTaskIdIndices, $expectedTaskDataCollection)
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare();

        $requestData = [];
        if (!is_null($requestTaskIdIndices)) {
            $requestData['taskIds'] = $this->createRequestTaskIdsFromRequestTaskIndices($job, $requestTaskIdIndices);
        }

        $tasksActionResponse = $this->jobController->tasksAction(
            new Request([], $requestData),
            $job->getWebsite()->getCanonicalUrl(),
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
}
