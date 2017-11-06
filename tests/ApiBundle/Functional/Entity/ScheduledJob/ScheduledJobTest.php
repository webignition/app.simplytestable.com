<?php

namespace Tests\ApiBundle\Functional\Entity\ScheduledJob;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use Cron\CronBundle\Entity\CronJob;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;


abstract class ScheduledJobTest extends AbstractBaseTestCase {


    /**
     * @return ScheduledJob
     */
    protected function getScheduledJob() {
        $scheduledJob = new ScheduledJob();
        $scheduledJob->setCronJob($this->getCronJob());
        $scheduledJob->setJobConfiguration($this->getJobConfiguration());
        $scheduledJob->setIsRecurring(true);

        return $scheduledJob;
    }


    /**
     * @return CronJob
     */
    protected function getCronJob() {
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
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setLabel('label');
        $jobConfiguration->setParameters('');
        $jobConfiguration->setType($fullSiteJobType);
        $jobConfiguration->setUser($userService->getPublicUser());
        $jobConfiguration->setWebsite($websiteService->fetch('http://example.com/'));

        return $jobConfiguration;
    }

}