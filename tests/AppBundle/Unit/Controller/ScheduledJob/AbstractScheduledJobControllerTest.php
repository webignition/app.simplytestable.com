<?php

namespace Tests\AppBundle\Unit\Controller\ScheduledJob;

use Mockery\Mock;
use AppBundle\Controller\ScheduledJobController;
use AppBundle\Services\ApplicationStateService;
use Symfony\Component\Routing\RouterInterface;
use Tests\AppBundle\Factory\MockFactory;
use AppBundle\Services\ScheduledJob\Service as ScheduledJobService;

abstract class AbstractScheduledJobControllerTest extends \PHPUnit\Framework\TestCase
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

        $scheduledJobController = new ScheduledJobController(
            $services['router'],
            $services[ApplicationStateService::class],
            $services[ScheduledJobService::class]
        );

        return $scheduledJobController;
    }
}
