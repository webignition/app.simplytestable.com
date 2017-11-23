<?php

namespace Tests\ApiBundle\Functional\Services\JobPreparation;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeDomainsToIgnoreService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Symfony\Component\HttpFoundation\Response;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\TaskControllerCompleteActionRequestFactory;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use SimplyTestable\ApiBundle\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;

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

        $taskController = $this->container->get(TaskController::class);

        $this->container->get('request_stack')->push($taskCompleteRequest);
        $this->container->get(CompleteRequestFactory::class)->init($taskCompleteRequest);

        $this->callTaskControllerCompleteAction($taskController);

        $this->assertEquals(JobService::FAILED_NO_SITEMAP_STATE, $parentJob->getState()->getName());
        $this->assertNull($parentJob->getTimePeriod());

        $this->jobPreparationService->prepareFromCrawl($crawlJobContainer);

        $this->assertEquals(JobService::QUEUED_STATE, $parentJob->getState()->getName());
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
            $this->assertEquals(TaskService::QUEUED_STATE, $task->getState()->getName());
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
        $jobService = $this->container->get(JobService::class);
        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);

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
            $this->container->get(ApplicationStateService::class),
            $this->container->get(ResqueQueueService::class),
            $this->container->get(ResqueJobFactory::class),
            $this->container->get(CompleteRequestFactory::class),
            $this->container->get(TaskService::class),
            $this->container->get(JobService::class),
            $this->container->get(JobPreparationService::class),
            $this->container->get(CrawlJobContainerService::class),
            $this->container->get(TaskOutputJoinerFactory::class),
            $this->container->get(TaskPostProcessorFactory::class),
            $this->container->get(StateService::class),
            $this->container->get(TaskTypeDomainsToIgnoreService::class)
        );
    }
}
