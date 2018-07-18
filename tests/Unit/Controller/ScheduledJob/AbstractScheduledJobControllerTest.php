<?php

namespace App\Tests\Unit\Controller\ScheduledJob;

use Mockery\Mock;
use App\Controller\ScheduledJobController;
use App\Services\ApplicationStateService;
use Symfony\Component\Routing\RouterInterface;
use App\Tests\Factory\MockFactory;
use App\Services\ScheduledJob\Service as ScheduledJobService;

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
