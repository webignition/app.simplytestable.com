<?php

namespace Tests\ApiBundle\Factory;

use Mockery\Mock;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ContainerFactory
{
    /**
     * @param array $services
     *
     * @return Mock|ContainerInterface
     */
    public static function create($services)
    {
        /* @var Mock|ContainerInterface $container */
        $container = \Mockery::mock(ContainerInterface::class);

        foreach ($services as $serviceId => $service) {
            $container
                ->shouldReceive('get')
                ->with($serviceId)
                ->andReturn($service);
        }

        return $container;
    }

    /**
     * @param array $maintenanceStates
     *
     * @return Mock|ContainerInterface
     */
    public static function createForMaintenanceReadOnlyModeControllerTest($maintenanceStates)
    {
        /* @var Mock|ApplicationStateService $applicationStateService */
        $applicationStateService = \Mockery::mock(ApplicationStateService::class);

        $applicationStateService
            ->shouldReceive('isInMaintenanceReadOnlyState')
            ->andReturn($maintenanceStates['read-only']);

        $applicationStateService
            ->shouldReceive('isInMaintenanceBackupReadOnlyState')
            ->andReturn($maintenanceStates['backup-read-only']);

        /* @var Mock|KernelInterface $kernel */
        $kernel = \Mockery::mock(KernelInterface::class);
        $kernel
            ->shouldReceive('locateResource')
            ->with('@SimplyTestableApiBundle/Resources/config/state/');

        $kernel
            ->shouldReceive('getEnvironment')
            ->andReturn('test');

        return static::create([
            ApplicationStateService::class => $applicationStateService,
            'kernel' => $kernel,
        ]);
    }
}
