<?php

namespace Tests\AppBundle\Unit\Controller\ScheduledJob;

use AppBundle\Services\ScheduledJob\Service as ScheduledJobService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tests\AppBundle\Factory\MockFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\AppBundle\Factory\ModelFactory;

/**
 * @group Controller/ScheduledJob
 */
class ScheduledJobControllerGetActionTest extends AbstractScheduledJobControllerTest
{
    const SCHEDULED_JOB_ID = 1;

    public function testGetActionScheduledJobNotFound()
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

        $scheduledJobController->getAction(self::SCHEDULED_JOB_ID);
    }

    public function testGetSuccess()
    {
        $scheduledJob = ModelFactory::createScheduledJob([
            ModelFactory::SCHEDULED_JOB_JOB_CONFIGURATION => ModelFactory::createJobConfiguration([
                ModelFactory::JOB_CONFIGURATION_LABEL => 'job configuration label',
            ]),
            ModelFactory::SCHEDULED_JOB_SCHEDULE => '* * * * *',
        ]);

        $scheduledJobController = $this->createScheduledJobController([
            ScheduledJobService::class => MockFactory::createScheduledJobService([
                'get' => [
                    'with' => self::SCHEDULED_JOB_ID,
                    'return' => $scheduledJob,
                ],
            ])
        ]);

        $response = $scheduledJobController->getAction(self::SCHEDULED_JOB_ID);

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
