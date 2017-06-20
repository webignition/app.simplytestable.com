<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use Mockery\MockInterface;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ContainerFactory
{
    /**
     * @param array $services
     *
     * @return MockInterface|ContainerInterface
     */
    public static function create($services)
    {
        $container = \Mockery::mock(ContainerInterface::class);

        foreach ($services as $serviceId => $service) {
            $container
                ->shouldReceive('get')
                ->with($serviceId)
                ->andReturn($service);
        }

        return $container;
    }

    public static function createForMaintenanceReadOnlyModeControllerTest()
    {
        $applicationStateService = \Mockery::mock(ApplicationStateService::class);
        $applicationStateService
            ->shouldReceive('setStateResourcePath');

        $applicationStateService
            ->shouldReceive('isInMaintenanceReadOnlyState')
            ->andReturn(true);

        $kernel = \Mockery::mock(KernelInterface::class);
        $kernel
            ->shouldReceive('locateResource')
            ->with('@SimplyTestableApiBundle/Resources/config/state/');

        $kernel
            ->shouldReceive('getEnvironment')
            ->andReturn('test');

        return static::create([
            'simplytestable.services.applicationStateService' => $applicationStateService,
            'kernel' => $kernel,
        ]);
    }
}
