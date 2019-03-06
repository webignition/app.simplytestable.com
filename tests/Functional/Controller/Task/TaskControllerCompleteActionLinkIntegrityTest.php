<?php

namespace App\Tests\Functional\Controller\Task;

use App\Entity\Job\Job;
use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Services\JobTypeService;
use App\Services\JobUserAccountPlanEnforcementService;
use App\Services\Request\Factory\Task\CompleteRequestFactory;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use App\Tests\Factory\TaskControllerCompleteActionRequestFactory;
use App\Tests\Services\JobFactory;
use webignition\InternetMediaType\InternetMediaType;

/**
 * @group Controller/TaskController
 */
class TaskControllerCompleteActionLinkIntegrityTest extends AbstractTaskControllerTest
{
    /**
     * @var Job
     */
    private $job;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $jobFactory = self::$container->get(JobFactory::class);
        $this->job = $jobFactory->createResolveAndPrepare([
            'siteRootUrl' => 'http://example.com/',
            'type' => JobTypeService::FULL_SITE_NAME,
            'testTypes' => ['link integrity'],
        ]);
    }

    /**
     * @dataProvider completeActionDataProvider
     *
     * @param array $outputValuesCollection
     * @param array $postData
     * @param array $routeParams
     * @param array $expectedTaskStates
     * @param array $expectedTaskOutputValuesCollection
     */
    public function testCompleteActionSuccess(
        $outputValuesCollection,
        $postData,
        $routeParams,
        $expectedTaskStates,
        $expectedTaskOutputValuesCollection
    ) {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $this->setJobTypeConstraintLimits();

        $tasks = $this->job->getTasks();

        $applicationJsonContentType = new InternetMediaType();
        $applicationJsonContentType->setType('application');
        $applicationJsonContentType->setSubtype('json');

        foreach ($outputValuesCollection as $outputIndex => $outputValues) {
            if (!empty($outputValues)) {
                /* @var Task $task */
                $task = $tasks->get($outputIndex);

                $output = new Output();
                $output->setOutput($outputValues['output']);
                $output->setErrorCount($outputValues['errorCount']);
                $output->setContentType($applicationJsonContentType);

                $task->setOutput($output);

                $entityManager->persist($output);
                $entityManager->persist($task);
                $entityManager->flush();
            }
        }

        $request = TaskControllerCompleteActionRequestFactory::create($postData, $routeParams);
        self::$container->get('request_stack')->push($request);

        $response = $this->callCompleteAction();

        $this->assertTrue($response->isSuccessful());

        foreach ($this->job->getTasks() as $taskIndex => $task) {
            $expectedTaskOutputValues = $expectedTaskOutputValuesCollection[$taskIndex];

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

    /**
     * @return array
     */
    public function completeActionDataProvider()
    {
        $now = new \DateTime();

        return [
            'no pre-existing output' => [
                'outputValuesCollection' => [
                    [],
                    [],
                    [],
                ],
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => 'application/json',
                    CompleteRequestFactory::PARAMETER_ERROR_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_OUTPUT => '[]',
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => 'link integrity',
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/one'),
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd41d8cd98f00b204e9800998ecf8427e',
                ],
                'expectedTaskStates' => [
                    'task-completed',
                    'task-queued',
                    'task-queued',
                ],
                'expectedTaskOutputValuesCollection' => [
                    [
                        'errorCount' => 0,
                        'warningCount' => 0,
                        'output' => '[]',
                    ],
                    null,
                    null,
                ],
            ],
            'pre-existing output, no new output' => [
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
                    [],
                    [],
                ],
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => 'application/json',
                    CompleteRequestFactory::PARAMETER_ERROR_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_OUTPUT => '[]',
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => 'link integrity',
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/one'),
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd41d8cd98f00b204e9800998ecf8427e',
                ],
                'expectedTaskStates' => [
                    'task-completed',
                    'task-queued',
                    'task-queued',
                ],
                'expectedTaskOutputValuesCollection' => [
                    [
                        'errorCount' => 1,
                        'warningCount' => 0,
                        'output' => json_encode([
                            [
                                'context' => '<a href="http://example.com/one">Example One</a>',
                                'state' => 404,
                                'type' => 'http',
                                'url' => 'http://example.com/one'
                            ],
                        ]),
                    ],
                    null,
                    null,
                ],
            ],
            'pre-existing output, new output' => [
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
                    [],
                    [],
                ],
                'postData' => [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => 'application/json',
                    CompleteRequestFactory::PARAMETER_ERROR_COUNT => 1,
                    CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                    CompleteRequestFactory::PARAMETER_OUTPUT => json_encode([
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
                ],
                'routeParams' => [
                    CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => 'link integrity',
                    CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/one'),
                    CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'd41d8cd98f00b204e9800998ecf8427e',
                ],
                'expectedTaskStates' => [
                    'task-completed',
                    'task-queued',
                    'task-queued',
                ],
                'expectedTaskOutputValuesCollection' => [
                    [
                        'errorCount' => 2,
                        'warningCount' => 0,
                        'output' => json_encode([
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
                    ],
                    null,
                    null,
                ],
            ],
        ];
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
