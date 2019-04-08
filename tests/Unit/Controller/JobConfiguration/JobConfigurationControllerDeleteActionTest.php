<?php

namespace App\Tests\Unit\Controller\JobConfiguration;

use App\Entity\Job\Configuration;
use App\Entity\ScheduledJob;
use App\Repository\ScheduledJobRepository;
use App\Services\ApplicationStateService;
use App\Services\Job\ConfigurationService;
use App\Tests\Factory\MockFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @group Controller/JobConfiguration
 */
class JobConfigurationControllerDeleteActionTest extends AbstractJobConfigurationControllerTest
{
    const JOB_CONFIGURATION_ID = 1;

    public function testDeleteActionInMaintenanceReadOnlyMode()
    {
        $jobConfigurationController = $this->createJobConfigurationController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $jobConfigurationController->deleteAction(
            \Mockery::mock(ScheduledJobRepository::class),
            self::JOB_CONFIGURATION_ID
        );
    }

    public function testDeleteActionJobConfigurationNotFound()
    {
        $jobConfigurationController = $this->createJobConfigurationController([
            ConfigurationService::class => MockFactory::createJobConfigurationService([
                'getById' => [
                    'with' => self::JOB_CONFIGURATION_ID,
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $jobConfigurationController->deleteAction(
            \Mockery::mock(ScheduledJobRepository::class),
            self::JOB_CONFIGURATION_ID
        );
    }

    public function testDeleteActionJobConfigurationBelongsToScheduledJob()
    {
        $jobConfiguration = new Configuration();

        $jobConfigurationController = $this->createJobConfigurationController([
            ConfigurationService::class => MockFactory::createJobConfigurationService([
                'getById' => [
                    'with' => self::JOB_CONFIGURATION_ID,
                    'return' => $jobConfiguration,
                ],
            ]),
        ]);

        $scheduledJobRepository = MockFactory::createScheduledJobRepository([
            'findOneBy' => [
                'with' => [
                    'jobConfiguration' => $jobConfiguration,
                ],
                'return' => new ScheduledJob(),
            ],
        ]);

        $response = $jobConfigurationController->deleteAction(
            $scheduledJobRepository,
            self::JOB_CONFIGURATION_ID
        );

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            '{"code":1,"message":"Job configuration is in use by a scheduled job"}',
            $response->headers->get('X-JobConfigurationDelete-Error')
        );
    }
}
