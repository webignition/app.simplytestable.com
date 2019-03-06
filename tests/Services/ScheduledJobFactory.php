<?php

namespace App\Tests\Services;

use App\Entity\ScheduledJob;
use App\Services\ScheduledJob\Service as ScheduledJobService;

class ScheduledJobFactory
{
    const KEY_JOB_CONFIGURATION = 'job-configuration';
    const KEY_SCHEDULE = 'schedule';
    const KEY_CRON_MODIFIER = 'cron-modifier';
    const KEY_IS_RECURRING = 'is-recurring';

    private $scheduledJobService;

    public function __construct(ScheduledJobService $scheduledJobService)
    {
        $this->scheduledJobService = $scheduledJobService;
    }

    /**
     * @param array $scheduledJobValues
     *
     * @return ScheduledJob
     */
    public function create($scheduledJobValues = [])
    {
        if (!array_key_exists(self::KEY_SCHEDULE, $scheduledJobValues)) {
            $scheduledJobValues[self::KEY_SCHEDULE] = '* * * * *';
        }

        if (!array_key_exists(self::KEY_CRON_MODIFIER, $scheduledJobValues)) {
            $scheduledJobValues[self::KEY_CRON_MODIFIER] = null;
        }

        if (!array_key_exists(self::KEY_IS_RECURRING, $scheduledJobValues)) {
            $scheduledJobValues[self::KEY_IS_RECURRING] = true;
        }

        $scheduledJob = $this->scheduledJobService->create(
            $scheduledJobValues[self::KEY_JOB_CONFIGURATION],
            $scheduledJobValues[self::KEY_SCHEDULE],
            $scheduledJobValues[self::KEY_CRON_MODIFIER],
            $scheduledJobValues[self::KEY_IS_RECURRING]
        );

        return $scheduledJob;
    }
}
