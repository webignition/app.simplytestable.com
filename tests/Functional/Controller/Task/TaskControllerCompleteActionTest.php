<?php

namespace App\Tests\Functional\Controller\Task;

use App\Entity\Job\Job;
use App\Entity\State;
use App\Entity\Task\Task;
use App\Services\JobTypeService;
use App\Services\JobUserAccountPlanEnforcementService;
use App\Services\Request\Factory\Task\CompleteRequestFactory;
use App\Services\StateService;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use App\Tests\Services\UserFactory;
use App\Tests\Factory\TaskControllerCompleteActionRequestFactory;
use App\Tests\Factory\TaskTypeFactory;
use App\Tests\Services\JobFactory;
use webignition\InternetMediaType\InternetMediaType;

/**
 * @group Controller/TaskController
 */
class TaskControllerCompleteActionTest extends AbstractTaskControllerTest
{
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

        $this->jobFactory = self::$container->get(JobFactory::class);
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
        $stateService = self::$container->get(StateService::class);

        $this->setJobTypeConstraintLimits();
        $userFactory = self::$container->get(UserFactory::class);

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
                $stateService->get(Task::STATE_IN_PROGRESS)
            );

            $jobs[] = $job;
        }

        $request = TaskControllerCompleteActionRequestFactory::create($postData, $routeParams);
        self::$container->get('request_stack')->push($request);

        $response = $this->callCompleteAction();

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
        $applicationJsonContentType = new InternetMediaType('application', 'json');
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
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/one'),
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd41d8cd98f00b204e9800998ecf8427e',
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
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/foo%20bar'),
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd41d8cd98f00b204e9800998ecf8427e',
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
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/bar foo'),
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd41d8cd98f00b204e9800998ecf8427e',
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
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/one'),
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd41d8cd98f00b204e9800998ecf8427e',
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
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/one'),
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd41d8cd98f00b204e9800998ecf8427e',
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
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/one'),
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
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/one'),
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
        $jobUserAccountPlanEnforcementService = self::$container->get(JobUserAccountPlanEnforcementService::class);
        $userService = self::$container->get(UserService::class);
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

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

        $entityManager->persist($fullSiteJobsPerSiteConstraint);
        $entityManager->persist($singleUrlJobsPerUrlConstraint);
        $entityManager->flush();
    }
}
