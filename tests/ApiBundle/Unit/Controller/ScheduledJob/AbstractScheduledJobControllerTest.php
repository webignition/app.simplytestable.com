<?php

namespace Tests\ApiBundle\Unit\Controller\ScheduledJob;

use Mockery\Mock;
use SimplyTestable\ApiBundle\Controller\ScheduledJobController;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Symfony\Component\Routing\RouterInterface;
use Tests\ApiBundle\Factory\MockFactory;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;

abstract class AbstractScheduledJobControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $services
     *
     * @return ScheduledJobController
     */
    protected function createScheduledJobController($services = [])
    {
        if (!isset($services['router'])) {
            /* @var RouterInterface|Mock $router */
            $router = \Mockery::mock(RouterInterface::class);

            $services['router'] = $router;
        }

        if (!isset($services[ScheduledJobService::class])) {
            $services[ScheduledJobService::class] = MockFactory::createScheduledJobService();
        }

        if (!isset($services[ApplicationStateService::class])) {
            $services[ApplicationStateService::class] = MockFactory::createApplicationStateService();
        }

        $jobConfigurationController = new ScheduledJobController(
            $services['router'],
            $services[ApplicationStateService::class],
            $services[ScheduledJobService::class]
        );

        return $jobConfigurationController;
    }
}
