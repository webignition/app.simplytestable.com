<?php

namespace App\Tests\Unit\Controller\JobConfiguration;

use App\Services\ApplicationStateService;
use App\Services\Job\ConfigurationService;
use App\Tests\Factory\MockFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @group Controller/JobConfiguration
 */
class JobConfigurationControllerUpdateActionTest extends AbstractJobConfigurationControllerTest
{
    const JOB_CONFIGURATION_ID = 1;

    public function testUpdateActionInMaintenanceReadOnlyMode()
    {
        $jobConfigurationController = $this->createJobConfigurationController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $jobConfigurationController->updateAction(
            MockFactory::createWebSiteService(),
            MockFactory::createTaskTypeService(),
            MockFactory::createJobTypeService(),
            new Request(),
            self::JOB_CONFIGURATION_ID
        );
    }

    public function testUpdateActionJobConfigurationNotFound()
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

        $jobConfigurationController->updateAction(
            MockFactory::createWebSiteService(),
            MockFactory::createTaskTypeService(),
            MockFactory::createJobTypeService(),
            new Request(),
            self::JOB_CONFIGURATION_ID
        );
    }
}
