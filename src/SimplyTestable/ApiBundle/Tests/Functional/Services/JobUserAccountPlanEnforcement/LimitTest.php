<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobUserAccountPlanEnforcement;

use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class LimitTest extends ServiceTest
{
    const FULL_SITE_JOBS_PER_SITE_LIMIT = 1;
    const SINGLE_URL_JOBS_PER_URL_LIMIT = 2;

    private $jobsCreatedBeforeLimitReached = 0;

    protected function setUp()
    {
        parent::setUp();

        $this->getJobUserAccountPlanEnforcementService()->setUser($this->getUserService()->getPublicUser());
        $this->setDifferentJobTypeLimits();

        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobCompletedState = $stateService->fetch(JobService::COMPLETED_STATE);

        $jobFactory = new JobFactory($this->container);

        for ($jobIndex = 0; $jobIndex <= $this->getJobTypeCreateLimit() - 1; $jobIndex++) {
            $this->jobsCreatedBeforeLimitReached++;

            $job = $jobFactory->create([
                JobFactory::KEY_TYPE => $this->getJobType(),
            ]);

            if (JobService::REJECTED_STATE === $job->getState()->getName()) {
                $this->fail('Job rejected before limit reached');
            }

            $now = new \DateTime();

            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime($now);
            $timePeriod->setEndDateTime($now);

            $job->setTimePeriod($timePeriod);

            $job->setState($jobCompletedState);
            $this->getJobService()->persistAndFlush($job);
        }
    }

    abstract protected function isFullSiteLimitTest();

    public function testLimitIsReached()
    {
        if ($this->isFullSiteLimitTest()) {
            $this->assertTrue(
                $this
                    ->getJobUserAccountPlanEnforcementService()
                    ->isFullSiteJobLimitReachedForWebSite($this->getWebsite())
            );
        } else {
            $this->assertTrue(
                $this
                    ->getJobUserAccountPlanEnforcementService()
                    ->isSingleUrlLimitReachedForWebsite($this->getWebsite())
            );
        }
    }

    public function testCorrectLimitIsApplied()
    {
        $this->assertEquals($this->getExpectedJobTypeCreateLimit(), $this->jobsCreatedBeforeLimitReached);
    }

    private function getJobType()
    {
        return $this->isFullSiteLimitTest()
            ? JobTypeService::FULL_SITE_NAME
            : JobTypeService::SINGLE_URL_NAME;
    }

    protected function getWebsite()
    {
        return $this->getWebSiteService()->fetch(self::DEFAULT_CANONICAL_URL);
    }

    protected function getExpectedJobTypeCreateLimit()
    {
            return $this->isFullSiteLimitTest()
                ? self::FULL_SITE_JOBS_PER_SITE_LIMIT
                : self::SINGLE_URL_JOBS_PER_URL_LIMIT;
    }

    private function setDifferentJobTypeLimits()
    {
        // Set full-site and single-page limits to different values to help verify that the correct constraint and
        // limit has been enforced
        $fullSiteJobsPerSiteConstraint =
            $this->getJobUserAccountPlanEnforcementService()->getFullSiteJobLimitConstraint();

        $singleUrlJobsPerUrlConstraint =
            $this->getJobUserAccountPlanEnforcementService()->getSingleUrlJobLimitConstraint();

        $fullSiteJobsPerSiteConstraint->setLimit(self::FULL_SITE_JOBS_PER_SITE_LIMIT);
        $singleUrlJobsPerUrlConstraint->setLimit(self::SINGLE_URL_JOBS_PER_URL_LIMIT);

        $this->getJobService()->getManager()->persist($fullSiteJobsPerSiteConstraint);
        $this->getJobService()->getManager()->persist($singleUrlJobsPerUrlConstraint);
    }

    private function getJobTypeCreateLimit()
    {
        if ($this->isFullSiteLimitTest()) {
            return $this->getJobUserAccountPlanEnforcementService()->getFullSiteJobLimitConstraint()->getLimit();
        }

        return $this->getJobUserAccountPlanEnforcementService()->getSingleUrlJobLimitConstraint()->getLimit();
    }
}
