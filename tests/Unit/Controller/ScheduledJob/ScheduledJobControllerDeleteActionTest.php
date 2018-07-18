<?php

namespace App\Tests\Unit\Controller\ScheduledJob;

use App\Entity\ScheduledJob;
use App\Services\ApplicationStateService;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use App\Tests\Factory\MockFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group Controller/ScheduledJob
 */
class ScheduledJobControllerDeleteActionTest extends AbstractScheduledJobControllerTest
{
    const SCHEDULED_JOB_ID = 1;

    public function testDeleteActionInMaintenanceReadOnlyMode()
    {
        $scheduledJobController = $this->createScheduledJobController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $scheduledJobController->deleteAction(self::SCHEDULED_JOB_ID);
    }

    public function testDeleteActionScheduledJobNotFound()
    {
        $scheduledJobController = $this->createScheduledJobController([
            ScheduledJobService::class => MockFactory::createScheduledJobService([
                'get' => [
                    'with' => self::SCHEDULED_JOB_ID,
                    'return' => null,
                ],
            ])
        ]);

        $this->expectException(NotFoundHttpException::class);

        $scheduledJobController->deleteAction(self::SCHEDULED_JOB_ID);
    }

    public function testDeleteSuccess()
    {
        $scheduledJob = new ScheduledJob();

        $scheduledJobController = $this->createScheduledJobController([
            ScheduledJobService::class => MockFactory::createScheduledJobService([
                'get' => [
                    'with' => self::SCHEDULED_JOB_ID,
                    'return' => $scheduledJob,
                ],
                'delete' => [
                    'with' => $scheduledJob
                ],
            ])
        ]);

        $response = $scheduledJobController->deleteAction(self::SCHEDULED_JOB_ID);

        $this->assertInstanceOf(Response::class, $response);
    }
}
