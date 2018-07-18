<?php

namespace App\Tests\Factory;

use App\Entity\ScheduledJob;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ScheduledJobFactory
{
    const KEY_JOB_CONFIGURATION = 'job-configuration';
    const KEY_SCHEDULE = 'schedule';
    const KEY_CRON_MODIFIER = 'cron-modifier';
    const KEY_IS_RECURRING = 'is-recurring';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $scheduledJobValues
     *
     * @return ScheduledJob
     */
    public function create($scheduledJobValues = [])
    {
        $scheduledJobService = $this->container->get(ScheduledJobService::class);

        if (!array_key_exists(self::KEY_SCHEDULE, $scheduledJobValues)) {
            $scheduledJobValues[self::KEY_SCHEDULE] = '* * * * *';
        }

        if (!array_key_exists(self::KEY_CRON_MODIFIER, $scheduledJobValues)) {
            $scheduledJobValues[self::KEY_CRON_MODIFIER] = null;
        }

        if (!array_key_exists(self::KEY_IS_RECURRING, $scheduledJobValues)) {
            $scheduledJobValues[self::KEY_IS_RECURRING] = true;
        }

        $scheduledJob = $scheduledJobService->create(
            $scheduledJobValues[self::KEY_JOB_CONFIGURATION],
            $scheduledJobValues[self::KEY_SCHEDULE],
            $scheduledJobValues[self::KEY_CRON_MODIFIER],
            $scheduledJobValues[self::KEY_IS_RECURRING]
        );

        return $scheduledJob;
    }
}
