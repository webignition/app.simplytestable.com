<?php

namespace Tests\ApiBundle\Unit\Controller\JobConfiguration;

use Mockery\Mock;
use SimplyTestable\ApiBundle\Controller\JobConfigurationController;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService;
use Symfony\Component\Routing\RouterInterface;
use Tests\ApiBundle\Factory\MockFactory;

abstract class AbstractJobConfigurationControllerTest extends \PHPUnit_Framework_TestCase
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
