<?php

namespace App\Tests\Unit\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Exception\Services\JobPreparation\Exception as JobPreparationException;
use App\Services\CrawlJobContainerService;
use App\Services\CrawlJobUrlCollector;
use App\Services\JobPreparationService;
use App\Services\JobService;
use App\Services\JobTypeService;
use App\Services\JobUserAccountPlanEnforcementService;
use App\Services\StateService;
use App\Services\TaskService;
use App\Services\UrlFinder;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use App\Tests\Factory\MockFactory;
use App\Tests\Factory\ModelFactory;

/**
 * @group Services/JobPreparationService
 */
class JobPreparationServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testPrepareJobInWrongState()
    {
        $job = ModelFactory::createJob([
            ModelFactory::JOB_STATE => ModelFactory::createState('foo'),
        ]);

        $jobPreparationService = $this->createJobPreparationService();

        $this->expectException(JobPreparationException::class);

        $jobPreparationService->prepare($job);
    }

    public function testPrepareFromCrawlParentJobInWrongState()
    {
        $crawlJobContainer = ModelFactory::createCrawlJobContainer([
            ModelFactory::CRAWL_JOB_CONTAINER_PARENT_JOB => ModelFactory::createJob([
                ModelFactory::JOB_STATE => ModelFactory::createState('foo'),
            ]),
        ]);

        $jobPreparationService = $this->createJobPreparationService();

        $this->expectException(JobPreparationException::class);

        $jobPreparationService->prepareFromCrawl($crawlJobContainer);
    }

    /**
     * @param array $services
     *
     * @return JobPreparationService
     */
    private function createJobPreparationService($services = [])
    {
        if (!isset($services[JobService::class])) {
            $services[JobService::class] = MockFactory::createJobService();
        }

        if (!isset($services[TaskService::class])) {
            $services[TaskService::class] = MockFactory::createTaskService();
        }

        if (!isset($services[JobTypeService::class])) {
            $services[JobTypeService::class] = MockFactory::createJobTypeService();
        }

        if (!isset($services[JobUserAccountPlanEnforcementService::class])) {
            $services[JobUserAccountPlanEnforcementService::class] =
                MockFactory::createJobUserAccountPlanEnforcementService();
        }

        if (!isset($services[CrawlJobContainerService::class])) {
            $services[CrawlJobContainerService::class] = MockFactory::createCrawlJobContainerService();
        }

        if (!isset($services[UserService::class])) {
            $services[UserService::class] = MockFactory::createUserService();
        }

        if (!isset($services[UrlFinder::class])) {
            $services[UrlFinder::class] = MockFactory::createUrlFinder();
        }

        if (!isset($services[StateService::class])) {
            $services[StateService::class] = MockFactory::createStateService();
        }

        if (!isset($services[UserAccountPlanService::class])) {
            $services[UserAccountPlanService::class] = MockFactory::createUserAccountPlanService();
        }

        if (!isset($services[EntityManagerInterface::class])) {
            $services[EntityManagerInterface::class] = MockFactory::createEntityManager();
        }

        if (!isset($services[CrawlJobUrlCollector::class])) {
            $services[CrawlJobUrlCollector::class] = MockFactory::createCrawlJobUrlCollector();
        }

        $jobPreparationService = new JobPreparationService(
            $services[JobService::class],
            $services[TaskService::class],
            $services[JobTypeService::class],
            $services[JobUserAccountPlanEnforcementService::class],
            $services[CrawlJobContainerService::class],
            $services[UserService::class],
            $services[UrlFinder::class],
            $services[StateService::class],
            $services[UserAccountPlanService::class],
            $services[EntityManagerInterface::class],
            $services[CrawlJobUrlCollector::class]
        );

        return $jobPreparationService;
    }




}
