<?php

namespace Tests\ApiBundle\Functional\Entity\ScheduledJob;

use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use Cron\CronBundle\Entity\CronJob;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

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
        $userService = $this->container->get(UserService::class);
        $websiteService = $this->container->get(WebSiteService::class);
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');

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
