<?php

namespace Tests\ApiBundle\Functional\Controller\Task;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Factory\InternetMediaTypeFactory;
use Tests\ApiBundle\Factory\TaskControllerCompleteActionRequestFactory;
use Tests\ApiBundle\Factory\TaskTypeFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TaskControllerCompleteActionTest extends AbstractBaseTestCase
{
    /**
     * @var TaskController
     */
    private $taskController;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskController = new TaskController();
        $this->taskController->setContainer($this->container);

        $this->jobFactory = new JobFactory($this->container);
    }

    public function testCompleteActionInReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $this->container->get('request_stack')->push(new Request());

        try {
            $this->taskController->completeAction();
            $this->fail('ServiceUnavailableHttpException not thrown');
        } catch (ServiceUnavailableHttpException $serviceUnavailableHttpException) {
            $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
        }
    }

    /**
     * @dataProvider completeActionInvalidRequestDataProvider
     *
     * @param array $postData
     * @param array $routeParams
     */
    public function testCompleteActionInvalidRequest($postData, $routeParams)
    {
        $this->expectException(BadRequestHttpException::class);

        $request = TaskControllerCompleteActionRequestFactory::create($postData, $routeParams);
        $this->container->get('request_stack')->push($request);

        $this->taskController->completeAction();
    }

    /**
     * @return array
     */
    public function completeActionInvalidRequestDataProvider()
    {
        $htmlValidationTaskType = TaskTypeFactory::create('html validation');

        return [
            'no post data' => [
                'postData' => [],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'f4aa3479641e8bb1e2744857a3b687a5',
                ],
            ],
        ];
    }

    /**
     * @dataProvider completeActionNoMatchingTasksDataProvider
     *
     * @param array $postData
     * @param array $routeParams
     */
    public function testCompleteActionNoMatchingTasks($postData, $routeParams)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $this->expectException(GoneHttpException::class);

        $job = $this->jobFactory->createResolveAndPrepare([
            'type' => JobTypeService::FULL_SITE_NAME,
            'siteRootUrl' => 'http://example.com',
            'testTypes' => ['html validation',],
            'testTypeOptions' => [],
            'parameters' => [],
            'user' => $this->container->get('simplytestable.services.userservice')->getPublicUser()
        ]);

        $this->setJobTaskStates(
            $job,
            $stateService->fetch(TaskService::IN_PROGRESS_STATE)
        );

        $request = TaskControllerCompleteActionRequestFactory::create($postData, $routeParams);
        $this->container->get('request_stack')->push($request);

        $this->taskController->completeAction();
    }

    /**
     * @return array
     */
    public function completeActionNoMatchingTasksDataProvider()
    {
        $htmlValidationTaskType = TaskTypeFactory::create('html validation');
        $applicationJsonContentType = InternetMediaTypeFactory::create('application', 'json');
        $now = new \DateTime();

        return [
            'invalid task type' => [
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => 'foo',
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'f4aa3479641e8bb1e2744857a3b687a5',
                ],
            ],
            'incorrect parameter hash, no matching tasks' => [
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/one',
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'f4aa3479641e8bb1e2744857a3b687a5',
                ],
            ],
            'incorrect canonical url, no matching tasks' => [
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd751713988987e9331980363e24189ce',
                ],
            ],
        ];
    }

    /**
     * @dataProvider completeActionDataProvider
     *
     * @param array $jobCollectionData
     * @param array $postData
     * @param array $routeParams
     * @param array $expectedJobTaskStates
     * @param array $expectedJobTaskOutputValues
     */
    public function testCompleteActionSuccess(
        $jobCollectionData,
        $postData,
        $routeParams,
        $expectedJobTaskStates,
        $expectedJobTaskOutputValues
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $this->setJobTypeConstraintLimits();
        $userFactory = new UserFactory($this->container);

        /* @var Job[] $jobs */
        $jobs = [];
        foreach ($jobCollectionData as $jobValues) {
            $user = $userFactory->create([
                UserFactory::KEY_EMAIL => $jobValues['user'],
            ]);
            $jobValues['user'] = $user;

            $job = $this->jobFactory->createResolveAndPrepare($jobValues);

            $this->setJobTaskStates(
                $job,
                $stateService->fetch(TaskService::IN_PROGRESS_STATE)
            );

            $jobs[] = $job;
        }

        $request = TaskControllerCompleteActionRequestFactory::create($postData, $routeParams);
        $this->container->get('request_stack')->push($request);

        $response = $this->taskController->completeAction();

        $this->assertTrue($response->isSuccessful());

        foreach ($jobs as $jobIndex => $job) {
            $expectedTaskStates = $expectedJobTaskStates[$jobIndex];

            foreach ($job->getTasks() as $taskIndex => $task) {
                $expectedTaskOutputValues = $expectedJobTaskOutputValues[$jobIndex][$taskIndex];

                /* @var Task $task */
                $this->assertEquals($expectedTaskStates[$taskIndex], $task->getState());

                if (is_null($expectedTaskOutputValues)) {
                    $this->assertNull($task->getOutput());
                } else {
                    $taskOutput = $task->getOutput();

                    $this->assertEquals($expectedTaskOutputValues['errorCount'], $taskOutput->getErrorCount());
                    $this->assertEquals($expectedTaskOutputValues['warningCount'], $taskOutput->getWarningCount());
                    $this->assertEquals($expectedTaskOutputValues['output'], $taskOutput->getOutput());
                }
            }
        }
    }

    /**
     * @return array
     */
    public function completeActionDataProvider()
    {
        $htmlValidationTaskType = TaskTypeFactory::create('html validation');
        $cssValidationTaskType = TaskTypeFactory::create('css validation');
        $applicationJsonContentType = InternetMediaTypeFactory::create('application', 'json');
        $now = new \DateTime();

        return [
            'single user, single job, single matching task' => [
                'jobCollectionData' => [
                    [
                        'siteRootUrl' => 'http://example.com',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'user' => 'user1@example.com',
                        'testTypes' => [$htmlValidationTaskType->getName(),],
                        'testTypeOptions' => [],
                        'parameters' => [],
                    ],
                ],
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                    CompleteRequestFactory::PARAMETER_ERROR_COUNT => 1,
                    CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_OUTPUT => '[]',
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/one',
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd751713988987e9331980363e24189ce',
                ],
                'expectedJobTaskStates' => [
                    [
                        'task-completed',
                        'task-in-progress',
                        'task-in-progress',
                    ],
                ],
                'expectedJobTaskOutputValues' => [
                    [
                        [
                            'errorCount' => 1,
                            'warningCount' => 0,
                            'output' => '[]',
                        ],
                        null,
                        null,
                    ],
                ],
            ],
            'single user, single job, single matching task with url encoded in complete request' => [
                'jobCollectionData' => [
                    [
                        'siteRootUrl' => 'http://example.com',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'user' => 'user1@example.com',
                        'testTypes' => [$htmlValidationTaskType->getName(),],
                        'testTypeOptions' => [],
                        'parameters' => [],
                    ],
                ],
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                    CompleteRequestFactory::PARAMETER_ERROR_COUNT => 1,
                    CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_OUTPUT => '[]',
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/foo%20bar',
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd751713988987e9331980363e24189ce',
                ],
                'expectedJobTaskStates' => [
                    [
                        'task-in-progress',
                        'task-in-progress',
                        'task-completed',
                    ],
                ],
                'expectedJobTaskOutputValues' => [
                    [
                        null,
                        null,
                        [
                            'errorCount' => 1,
                            'warningCount' => 0,
                            'output' => '[]',
                        ],
                    ],
                ],
            ],
            'single user, single job, single matching task with url encoded in task' => [
                'jobCollectionData' => [
                    [
                        'siteRootUrl' => 'http://example.com',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'user' => 'user1@example.com',
                        'testTypes' => [$htmlValidationTaskType->getName(),],
                        'testTypeOptions' => [],
                        'parameters' => [],
                    ],
                ],
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                    CompleteRequestFactory::PARAMETER_ERROR_COUNT => 1,
                    CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_OUTPUT => '[]',
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/bar foo',
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd751713988987e9331980363e24189ce',
                ],
                'expectedJobTaskStates' => [
                    [
                        'task-in-progress',
                        'task-completed',
                        'task-in-progress',
                    ],
                ],
                'expectedJobTaskOutputValues' => [
                    [
                        null,
                        [
                            'errorCount' => 1,
                            'warningCount' => 0,
                            'output' => '[]',
                        ],
                        null,
                    ],
                ],
            ],
            'single user, two jobs, two matching tasks' => [
                'jobCollectionData' => [
                    [
                        'siteRootUrl' => 'http://example.com',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'user' => 'user1@example.com',
                        'testTypes' => [$htmlValidationTaskType->getName(),],
                        'testTypeOptions' => [],
                        'parameters' => [],
                    ],
                    [
                        'siteRootUrl' => 'http://example.com',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'user' => UserService::PUBLIC_USER_EMAIL_ADDRESS,
                        'testTypes' => [
                            $htmlValidationTaskType->getName(),
                            $cssValidationTaskType->getName(),
                        ],
                        'testTypeOptions' => [],
                        'parameters' => [],
                    ],
                ],
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                    CompleteRequestFactory::PARAMETER_ERROR_COUNT => 1,
                    CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_OUTPUT => '[]',
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/one',
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd751713988987e9331980363e24189ce',
                ],
                'expectedJobTaskStates' => [
                    [
                        'task-completed',
                        'task-in-progress',
                        'task-in-progress',
                    ],
                    [
                        'task-completed',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                    ],
                ],
                'expectedJobTaskOutputValues' => [
                    [
                        [
                            'errorCount' => 1,
                            'warningCount' => 0,
                            'output' => '[]',
                        ],
                        null,
                        null,
                    ],
                    [
                        [
                            'errorCount' => 1,
                            'warningCount' => 0,
                            'output' => '[]',
                        ],
                        null,
                        null,
                        null,
                        null,
                        null,
                    ],
                ],
            ],
            'two users, one job each, four matching tasks' => [
                'jobCollectionData' => [
                    [
                        'siteRootUrl' => 'http://example.com',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'user' => 'user1@example.com',
                        'testTypes' => [$htmlValidationTaskType->getName(),],
                        'testTypeOptions' => [],
                        'parameters' => [],
                    ],
                    [
                        'siteRootUrl' => 'http://example.com',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'user' => 'user2@example.com',
                        'testTypes' => [
                            $htmlValidationTaskType->getName(),
                            $cssValidationTaskType->getName(),
                        ],
                        'testTypeOptions' => [],
                        'parameters' => [],
                    ],
                ],
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                    CompleteRequestFactory::PARAMETER_ERROR_COUNT => 1,
                    CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_OUTPUT => '[]',
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/one',
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd751713988987e9331980363e24189ce',
                ],
                'expectedJobTaskStates' => [
                    [
                        'task-completed',
                        'task-in-progress',
                        'task-in-progress',
                    ],
                    [
                        'task-completed',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                    ],
                ],
                'expectedJobTaskOutputValues' => [
                    [
                        [
                            'errorCount' => 1,
                            'warningCount' => 0,
                            'output' => '[]',
                        ],
                        null,
                        null,
                    ],
                    [
                        [
                            'errorCount' => 1,
                            'warningCount' => 0,
                            'output' => '[]',
                        ],
                        null,
                        null,
                        null,
                        null,
                        null,
                    ],
                ],
            ],
            'single user, two jobs with different parameters, one matching task' => [
                'jobCollectionData' => [
                    [
                        'siteRootUrl' => 'http://example.com',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'user' => 'user1@example.com',
                        'testTypes' => [$htmlValidationTaskType->getName(),],
                        'parameters' => [
                            'foo1' => 'bar1',
                        ],
                        'testTypeOptions' => [],
                    ],
                    [
                        'siteRootUrl' => 'http://example.com',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'user' => UserService::PUBLIC_USER_EMAIL_ADDRESS,
                        'testTypes' => [
                            $htmlValidationTaskType->getName(),
                            $cssValidationTaskType->getName(),
                        ],
                        'parameters' => [
                            'foo2' => 'bar2',
                        ],
                        'testTypeOptions' => [],
                    ],
                ],
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                    CompleteRequestFactory::PARAMETER_ERROR_COUNT => 1,
                    CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_OUTPUT => '[]',
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/one',
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => '0b9de246e13147873511c378ae0cb9ef',
                ],
                'expectedJobTaskStates' => [
                    [
                        'task-completed',
                        'task-in-progress',
                        'task-in-progress',
                    ],
                    [
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                    ],
                ],
                'expectedJobTaskOutputValues' => [
                    [
                        [
                            'errorCount' => 1,
                            'warningCount' => 0,
                            'output' => '[]',
                        ],
                        null,
                        null,
                    ],
                    [
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                    ],
                ],
            ],
            'single user, two jobs with different task type options, one matching task' => [
                'jobCollectionData' => [
                    [
                        'siteRootUrl' => 'http://example.com',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'user' => 'user1@example.com',
                        'testTypes' => [$cssValidationTaskType->getName(),],
                        'testTypeOptions' => [
                            'css validation' => array(
                                'ignore-warnings' => 1,
                                'ignore-common-cdns' => 1,
                                'vendor-extensions' => 'warn'
                            )
                        ],
                        'parameters' => [],
                    ],
                    [
                        'siteRootUrl' => 'http://example.com',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'user' => UserService::PUBLIC_USER_EMAIL_ADDRESS,
                        'testTypes' => [
                            $htmlValidationTaskType->getName(),
                            $cssValidationTaskType->getName(),
                        ],
                        'testTypeOptions' => [
                            'css validation' => array(
                                'ignore-warnings' => 0,
                                'ignore-common-cdns' => 1,
                                'vendor-extensions' => 'warn'
                            )
                        ],
                        'parameters' => [],
                    ],
                ],
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                    CompleteRequestFactory::PARAMETER_ERROR_COUNT => 1,
                    CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_OUTPUT => '[]',
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $cssValidationTaskType->getName(),
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/one',
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => '26ce2b357c9841bf2ae2f9c624c2df39',
                ],
                'expectedJobTaskStates' => [
                    [
                        'task-completed',
                        'task-in-progress',
                        'task-in-progress',
                    ],
                    [
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                        'task-in-progress',
                    ],
                ],
                'expectedJobTaskOutputValues' => [
                    [
                        [
                            'errorCount' => 1,
                            'warningCount' => 0,
                            'output' => '[]',
                        ],
                        null,
                        null,
                    ],
                    [
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param Job $job
     * @param State $state
     */
    private function setJobTaskStates(Job $job, State $state)
    {
        $this->jobFactory->setTaskStates(
            $job,
            $state
        );
    }

    private function setJobTypeConstraintLimits()
    {
        $jobService = $this->container->get('simplytestable.services.jobservice');
        $jobUserAccountPlanEnforcementService = $this->container->get(
            'simplytestable.services.jobuseraccountplanenforcementservice'
        );
        $userService = $this->container->get('simplytestable.services.userservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');

        $user = $userService->getPublicUser();
        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $plan = $userAccountPlan->getPlan();

        $jobUserAccountPlanEnforcementService->setUser($user);

        $fullSiteJobsPerSiteConstraint = $plan->getConstraintNamed(
            JobUserAccountPlanEnforcementService::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME
        );

        $singleUrlJobsPerUrlConstraint = $plan->getConstraintNamed(
            JobUserAccountPlanEnforcementService::SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME
        );

        $fullSiteJobsPerSiteConstraint->setLimit(10);
        $singleUrlJobsPerUrlConstraint->setLimit(10);

        $jobService->getManager()->persist($fullSiteJobsPerSiteConstraint);
        $jobService->getManager()->persist($singleUrlJobsPerUrlConstraint);
    }
}