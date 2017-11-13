<?php

namespace Tests\ApiBundle\Unit\Controller\Job\Job;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Controller\Job\JobController;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\Job\RetrievalService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskTypeDomainsToIgnoreService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\RouterInterface;
use Tests\ApiBundle\Factory\MockFactory;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

/**
 * @group Controller/Job/JobController
 */
class JobControllerCancelActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';
    const JOB_ID = 1;

    public function testCancelActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = MockFactory::createApplicationStateService(true);

        $this->expectException(ServiceUnavailableHttpException::class);

        $jobController = $this->createJobController();

        $jobController->cancelAction(
            $applicationStateService,
            \Mockery::mock(JobService::class),
            \Mockery::mock(CrawlJobContainerService::class),
            \Mockery::mock(JobPreparationService::class),
            \Mockery::mock(ResqueQueueService::class),
            \Mockery::mock(ResqueJobFactory::class),
            \Mockery::mock(StateService::class),
            \Mockery::mock(TaskTypeDomainsToIgnoreService::class),
            self::CANONICAL_URL,
            self::JOB_ID
        );
    }

    public function testCancelActionJobRetrievalFailure()
    {
        $applicationStateService = MockFactory::createApplicationStateService(false);
        $jobRetrievalService = MockFactory::createJobRetrievalService([
            'retrieve' => [
                'with' => self::JOB_ID,
                'throw' => new JobRetrievalServiceException(),
            ],
        ]);

        $jobController = $this->createJobController(
            $jobRetrievalService
        );

        $this->expectException(AccessDeniedHttpException::class);

        $jobController->cancelAction(
            $applicationStateService,
            \Mockery::mock(JobService::class),
            \Mockery::mock(CrawlJobContainerService::class),
            \Mockery::mock(JobPreparationService::class),
            \Mockery::mock(ResqueQueueService::class),
            \Mockery::mock(ResqueJobFactory::class),
            \Mockery::mock(StateService::class),
            \Mockery::mock(TaskTypeDomainsToIgnoreService::class),
            self::CANONICAL_URL,
            self::JOB_ID
        );
    }
}
