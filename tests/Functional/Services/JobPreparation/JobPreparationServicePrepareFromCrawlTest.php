<?php

namespace App\Tests\Functional\Services\JobPreparation;

use App\Controller\TaskController;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Entity\TimePeriod;
use App\Entity\User;
use App\Services\CrawlJobContainerService;
use App\Services\JobPreparationService;
use App\Services\JobService;
use App\Services\JobUserAccountPlanEnforcementService;
use App\Services\Request\Factory\Task\CompleteRequestFactory;
use App\Services\StateService;
use App\Services\TaskService;
use App\Services\TaskTypeService;
use App\Services\UserAccountPlanService;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Factory\HttpFixtureFactory;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\MockFactory;
use App\Tests\Factory\TaskControllerCompleteActionRequestFactory;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use App\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;

/**
 * @group Services/JobPreparationService
 */
class JobPreparationServicePrepareFromCrawlTest extends AbstractJobPreparationServiceTest
{
    /**
     * @var User
     */
    private $user;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->user = $this->userFactory->create();
    }

    /**
     * @dataProvider prepareFromCrawlDataProvider
     *
     * @param array $jobValues
     * @param string[] $discoveredUrls
     * @param array $expectedTaskValuesCollection
     */
    public function testPrepareFromCrawl($jobValues, $discoveredUrls, $expectedTaskValuesCollection)
    {
        $parentJob = $this->jobFactory->createResolveAndPrepare(array_merge($jobValues, [
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
            JobFactory::KEY_USER => $this->user,
        ]), [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainer = $this->crawlJobContainerService->getForJob($parentJob);
        $urlDiscoveryTask = $crawlJobContainer->getCrawlJob()->getTasks()->first();

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($discoveredUrls),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $urlDiscoveryTask->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $urlDiscoveryTask->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $urlDiscoveryTask->getParametersHash(),
        ]);

        $taskController = self::$container->get(TaskController::class);

        self::$container->get('request_stack')->push($taskCompleteRequest);
        self::$container->get(CompleteRequestFactory::class)->init($taskCompleteRequest);

        $this->callTaskControllerCompleteAction($taskController);

        $this->assertEquals(Job::STATE_FAILED_NO_SITEMAP, $parentJob->getState()->getName());
        $this->assertNull($parentJob->getTimePeriod());

        $this->jobPreparationService->prepareFromCrawl($crawlJobContainer);

        $this->assertEquals(Job::STATE_QUEUED, $parentJob->getState()->getName());
        $this->assertInstanceOf(TimePeriod::class, $parentJob->getTimePeriod());

        $timePeriod = $parentJob->getTimePeriod();
        $this->assertInstanceOf(\DateTime::class, $timePeriod->getStartDateTime());
        $this->assertNull($timePeriod->getEndDateTime());

        /* @var Task[] $tasks */
        $tasks = $parentJob->getTasks()->toArray();

        $this->assertCount(count($expectedTaskValuesCollection), $tasks);

        foreach ($tasks as $taskIndex => $task) {
            $expectedTaskValues = $expectedTaskValuesCollection[$taskIndex];

            $this->assertNull($task->getWorker());
            $this->assertEquals(Task::STATE_QUEUED, $task->getState()->getName());
            $this->assertEquals($expectedTaskValues['url'], $task->getUrl());
            $this->assertEquals($expectedTaskValues['taskTypeName'], $task->getType()->getName());
        }
    }

    /**
     * @return array
     */
    public function prepareFromCrawlDataProvider()
    {
        return [
            'single discovered url, single task type' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
                'discoveredUrls' => [
                    'http://example.com/0/'
                ],
                'expectedSerializedTasks' => [
                    [
                        'url' => 'http://example.com/',
                        'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                    [
                        'url' => 'http://example.com/0/',
                        'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
            ],
            'single discovered url, two task types' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                        TaskTypeService::CSS_VALIDATION_TYPE,
                    ],
                ],
                'discoveredUrls' => [
                    'http://example.com/0/'
                ],
                'expectedSerializedTasks' => [
                    [
                        'url' => 'http://example.com/',
                        'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                    [
                        'url' => 'http://example.com/',
                        'taskTypeName' => TaskTypeService::CSS_VALIDATION_TYPE,
                    ],
                    [
                        'url' => 'http://example.com/0/',
                        'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                    [
                        'url' => 'http://example.com/0/',
                        'taskTypeName' => TaskTypeService::CSS_VALIDATION_TYPE,
                    ],
                ],
            ],
        ];
    }

    public function testPrepareFromCrawlWhereCrawlJobHasAmmendments()
    {
        $jobService = self::$container->get(JobService::class);
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);

        $parentJob = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => [
                TaskTypeService::HTML_VALIDATION_TYPE,
            ],
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
            JobFactory::KEY_USER => $this->user,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainer = $this->crawlJobContainerService->getForJob($parentJob);

        $crawlJob = $crawlJobContainer->getCrawlJob();

        $userAccountPlan = $userAccountPlanService->getForUser($crawlJob->getUser());
        $plan = $userAccountPlan->getPlan();
        $urlsPerJobConstraint = $plan->getConstraintNamed(
            JobUserAccountPlanEnforcementService::URLS_PER_JOB_CONSTRAINT_NAME
        );

        $jobService->addAmmendment(
            $crawlJob,
            'plan-url-limit-reached:discovered-url-count-12',
            $urlsPerJobConstraint
        );

        $this->assertEmpty($parentJob->getAmmendments());

        $this->jobPreparationService->prepareFromCrawl($crawlJobContainer);

        $this->assertCount(1, $parentJob->getAmmendments());

        $crawlJobAmmendment = $crawlJob->getAmmendments()->first();
        $parentJobAmmendment = $parentJob->getAmmendments()->first();

        $this->assertEquals($crawlJobAmmendment->jsonSerialize(), $parentJobAmmendment->jsonSerialize());
    }

    /**
     * @param TaskController $taskController
     *
     * @return Response
     */
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
