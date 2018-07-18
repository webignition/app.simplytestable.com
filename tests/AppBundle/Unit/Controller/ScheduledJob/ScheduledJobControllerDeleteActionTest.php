<?php

namespace Tests\AppBundle\Unit\Controller\ScheduledJob;

use AppBundle\Entity\ScheduledJob;
use AppBundle\Services\ApplicationStateService;
use AppBundle\Services\ScheduledJob\Service as ScheduledJobService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\AppBundle\Factory\MockFactory;
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
