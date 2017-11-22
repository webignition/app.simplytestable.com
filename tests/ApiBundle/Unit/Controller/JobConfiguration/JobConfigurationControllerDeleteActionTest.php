<?php

namespace Tests\ApiBundle\Unit\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService;
use Tests\ApiBundle\Factory\MockFactory;
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
            MockFactory::createEntityManager(),
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
            MockFactory::createEntityManager(),
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

        $entityManager = MockFactory::createEntityManager([
            'getRepository' => [
                'with' => ScheduledJob::class,
                'return' => $scheduledJobRepository,
            ],
        ]);

        $response = $jobConfigurationController->deleteAction(
            $entityManager,
            self::LABEL
        );

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            '{"code":1,"message":"Job configuration is in use by a scheduled job"}',
            $response->headers->get('X-JobConfigurationDelete-Error')
        );
    }
}
