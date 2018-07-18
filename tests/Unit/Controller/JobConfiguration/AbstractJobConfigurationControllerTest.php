<?php

namespace App\Tests\Unit\Controller\JobConfiguration;

use Mockery\Mock;
use App\Controller\JobConfigurationController;
use App\Services\ApplicationStateService;
use App\Services\Job\ConfigurationService;
use Symfony\Component\Routing\RouterInterface;
use App\Tests\Factory\MockFactory;

abstract class AbstractJobConfigurationControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $services
     *
     * @return JobConfigurationController
     */
    protected function createJobConfigurationController($services = [])
    {
        if (!isset($services['router'])) {
            /* @var RouterInterface|Mock $router */
            $router = \Mockery::mock(RouterInterface::class);

            $services['router'] = $router;
        }

        if (!isset($services[ApplicationStateService::class])) {
            $services[ApplicationStateService::class] = MockFactory::createApplicationStateService();
        }

        if (!isset($services[ConfigurationService::class])) {
            $services[ConfigurationService::class] = MockFactory::createJobConfigurationService();
        }

        $jobConfigurationController = new JobConfigurationController(
            $services['router'],
            $services[ApplicationStateService::class],
            $services[ConfigurationService::class]
        );

        return $jobConfigurationController;
    }
}
