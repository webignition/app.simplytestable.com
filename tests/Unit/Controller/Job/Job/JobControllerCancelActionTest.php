<?php

namespace App\Tests\Unit\Controller\Job\Job;

use App\Services\Job\RetrievalService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use App\Tests\Factory\MockFactory;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

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
            MockFactory::createJobService(),
            MockFactory::createCrawlJobContainerService(),
            MockFactory::createJobPreparationService(),
            MockFactory::createResqueQueueService(),
            MockFactory::createStateService(),
            MockFactory::createTaskTypeDomainsToIgnoreService(),
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

        $jobController = $this->createJobController([
            RetrievalService::class => $jobRetrievalService,
        ]);

        $this->expectException(AccessDeniedHttpException::class);

        $jobController->cancelAction(
            $applicationStateService,
            MockFactory::createJobService(),
            MockFactory::createCrawlJobContainerService(),
            MockFactory::createJobPreparationService(),
            MockFactory::createResqueQueueService(),
            MockFactory::createStateService(),
            MockFactory::createTaskTypeDomainsToIgnoreService(),
            self::JOB_ID
        );
    }
}
