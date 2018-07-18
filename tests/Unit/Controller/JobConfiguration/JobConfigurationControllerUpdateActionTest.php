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
    const LABEL = 'foo';

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
            self::LABEL
        );
    }

    public function testUpdateActionJobConfigurationNotFound()
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

        $jobConfigurationController->updateAction(
            MockFactory::createWebSiteService(),
            MockFactory::createTaskTypeService(),
            MockFactory::createJobTypeService(),
            new Request(),
            self::LABEL
        );
    }
}
