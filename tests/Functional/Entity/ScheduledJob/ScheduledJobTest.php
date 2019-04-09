<?php

namespace App\Tests\Functional\Entity\ScheduledJob;

use App\Services\JobTypeService;
use App\Services\UserService;
use App\Services\WebSiteService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\ScheduledJob;
use Cron\CronBundle\Entity\CronJob;
use App\Entity\Job\Configuration as JobConfiguration;
use App\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

abstract class ScheduledJobTest extends AbstractBaseTestCase
{
    /**
     * @return ScheduledJob
     */
    protected function getScheduledJob()
    {
        $scheduledJob = new ScheduledJob();
        $scheduledJob->setCronJob($this->createCronJob());
        $scheduledJob->setJobConfiguration($this->createJobConfiguration());
        $scheduledJob->setIsRecurring(true);

        return $scheduledJob;
    }

    protected function createCronJob(): CronJob
    {
        $cronJob = new CronJob();
        $cronJob->setName('cron job name');
        $cronJob->setCommand('ls');
        $cronJob->setDescription('cron job description');
        $cronJob->setEnabled(true);
        $cronJob->setSchedule('* * * * *');

        return $cronJob;
    }

    protected function createJobConfiguration(): JobConfiguration
    {
        $userService = self::$container->get(UserService::class);
        $websiteService = self::$container->get(WebSiteService::class);
        $jobTypeService = self::$container->get(JobTypeService::class);

        $fullSiteJobType = $jobTypeService->getFullSiteType();

        $jobConfiguration = JobConfiguration::create(
            'label',
            $userService->getPublicUser(),
            $websiteService->get('http://example.com/'),
            $fullSiteJobType,
            new TaskConfigurationCollection(),
            '[]'
        );

        return $jobConfiguration;
    }
}
