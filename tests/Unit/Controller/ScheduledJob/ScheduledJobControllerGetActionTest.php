<?php

namespace App\Tests\Unit\Controller\ScheduledJob;

use App\Entity\Job\Configuration;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Tests\Factory\MockFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Tests\Factory\ModelFactory;

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
        $configuration = \Mockery::mock(Configuration::class);
        $configuration
            ->shouldReceive('getLabel')
            ->andReturn('foo');

        $scheduledJob = ModelFactory::createScheduledJob([
            ModelFactory::SCHEDULED_JOB_JOB_CONFIGURATION => $configuration,
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
