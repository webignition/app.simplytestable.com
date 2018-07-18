<?php

namespace Tests\AppBundle\Unit\Controller\JobConfiguration;

use AppBundle\Services\ApplicationStateService;
use AppBundle\Services\Job\ConfigurationService;
use Tests\AppBundle\Factory\MockFactory;
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
