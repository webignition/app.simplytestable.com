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
    const LABEL = 'foo';

    public function testDeleteActionInMaintenanceReadOnlyMode()
    {
        $jobConfigurationController = $this->createJobConfigurationController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $jobConfigurationController->deleteAction(
            \Mockery::mock(ScheduledJobRepository::class),
            self::LABEL
        );
    }

    public function testDeleteActionJobConfigurationNotFound()
    {
        $jobConfigurationController = $this->createJobConfigurationController([
            ConfigurationService::class => MockFactory::createJobConfigurationService([
                'get' => [
                    'with' => self::LABEL,
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $jobConfigurationController->deleteAction(
            \Mockery::mock(ScheduledJobRepository::class),
            'foo'
        );
    }

    public function testDeleteActionJobConfigurationBelongsToScheduledJob()
    {
        $jobConfiguration = new Configuration();

        $jobConfigurationController = $this->createJobConfigurationController([
            ConfigurationService::class => MockFactory::createJobConfigurationService([
                'get' => [
                    'with' => self::LABEL,
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
            self::LABEL
        );

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            '{"code":1,"message":"Job configuration is in use by a scheduled job"}',
            $response->headers->get('X-JobConfigurationDelete-Error')
        );
    }
}
