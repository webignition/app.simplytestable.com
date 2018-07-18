<?php

namespace Tests\AppBundle\Functional\Entity\ScheduledJob;

use AppBundle\Services\JobTypeService;
use AppBundle\Services\UserService;
use AppBundle\Services\WebSiteService;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Entity\ScheduledJob;
use Cron\CronBundle\Entity\CronJob;
use AppBundle\Entity\Job\Configuration as JobConfiguration;

abstract class ScheduledJobTest extends AbstractBaseTestCase
{
    /**
     * @return ScheduledJob
     */
    protected function getScheduledJob()
    {
        $scheduledJob = new ScheduledJob();
        $scheduledJob->setCronJob($this->getCronJob());
        $scheduledJob->setJobConfiguration($this->getJobConfiguration());
        $scheduledJob->setIsRecurring(true);

        return $scheduledJob;
    }

    /**
     * @return CronJob
     */
    protected function getCronJob()
    {
        $cronJob = new CronJob();
        $cronJob->setName('cron job name');
        $cronJob->setCommand('ls');
        $cronJob->setDescription('cron job description');
        $cronJob->setEnabled(true);
        $cronJob->setSchedule('* * * * *');

        return $cronJob;
    }

    /**
     * @return JobConfiguration
     */
    protected function getJobConfiguration()
    {
        $userService = self::$container->get(UserService::class);
        $websiteService = self::$container->get(WebSiteService::class);
        $jobTypeService = self::$container->get(JobTypeService::class);

        $fullSiteJobType = $jobTypeService->getFullSiteType();

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setLabel('label');
        $jobConfiguration->setParameters('');
        $jobConfiguration->setType($fullSiteJobType);
        $jobConfiguration->setUser($userService->getPublicUser());
        $jobConfiguration->setWebsite($websiteService->get('http://example.com/'));

        return $jobConfiguration;
    }
}
