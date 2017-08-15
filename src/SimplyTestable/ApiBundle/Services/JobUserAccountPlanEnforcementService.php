<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;

class JobUserAccountPlanEnforcementService
{
    const FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME = 'full_site_jobs_per_site';
    const SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME = 'single_url_jobs_per_url';
    const URLS_PER_JOB_CONSTRAINT_NAME = 'urls_per_job';
    const CREDITS_PER_MONTH_CONSTRAINT_NAME = 'credits_per_month';

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var User
     */
    private $user;

    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var JobTypeService
     */
    private $jobTypeService;

    /**
     * @param UserAccountPlanService $userAccountPlanService
     * @param JobService $jobService
     * @param TaskService $taskService
     * @param TeamService $teamService
     * @param JobTypeService $jobTypeService
     */
    public function __construct(
        UserAccountPlanService $userAccountPlanService,
        JobService $jobService,
        TaskService $taskService,
        TeamService $teamService,
        JobTypeService $jobTypeService
    ) {
        $this->userAccountPlanService = $userAccountPlanService;
        $this->jobService = $jobService;
        $this->taskService = $taskService;
        $this->teamService = $teamService;
        $this->jobTypeService = $jobTypeService;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param WebSite $website
     *
     * @return bool
     */
    public function isFullSiteJobLimitReachedForWebSite(WebSite $website)
    {
        $userAccountPlan = $this->userAccountPlanService->getForUser($this->user);

        if (!$userAccountPlan->getPlan()->hasConstraintNamed(self::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME)) {
            return false;
        }

        $jobRepository = $this->jobService->getEntityRepository();
        $jobType = $this->jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $currentCount = $jobRepository->getJobCountByUserAndJobTypeAndWebsiteForCurrentMonth(
            $this->user,
            $jobType,
            $website
        );

        if ($currentCount === 0) {
            return false;
        }

        return $currentCount >= $this->getFullSiteJobLimitConstraint()->getLimit();
    }

    /**
     * @param WebSite $website
     *
     * @return bool
     */
    public function isSingleUrlLimitReachedForWebsite(WebSite $website)
    {
        $userAccountPlan = $this->userAccountPlanService->getForUser($this->user);

        if (!$userAccountPlan->getPlan()->hasConstraintNamed(self::SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME)) {
            return false;
        }

        $jobRepository = $this->jobService->getEntityRepository();
        $jobType = $this->jobTypeService->getByName(JobTypeService::SINGLE_URL_NAME);

        $currentCount = $jobRepository->getJobCountByUserAndJobTypeAndWebsiteForCurrentMonth(
            $this->user,
            $jobType,
            $website
        );

        if ($currentCount === 0) {
            return false;
        }

        return $currentCount >= $this->getSingleUrlJobLimitConstraint()->getLimit();
    }


    /**
     * @param $urlCount
     *
     * @return bool
     */
    public function isJobUrlLimitReached($urlCount)
    {
        if ($urlCount === 0) {
            return false;
        }

        $userAccountPlan = $this->userAccountPlanService->getForUser($this->user);

        if (!$userAccountPlan->getPlan()->hasConstraintNamed(self::URLS_PER_JOB_CONSTRAINT_NAME)) {
            return false;
        }

        return $urlCount > $this->getJobUrlLimitConstraint()->getLimit();
    }


    /**
     * @return int
     */
    public function getCreditsUsedThisMonth()
    {
        return $this->taskService->getEntityRepository()->getCountByUsersAndStatesForCurrentMonth(
            $this->teamService->getPeopleForUser($this->user),
            array(
                $this->taskService->getCompletedState(),
                $this->taskService->getFailedNoRetryAvailableState(),
                $this->taskService->getFailedRetryAvailableState(),
                $this->taskService->getFailedRetryLimitReachedState(),
                $this->taskService->getSkippedState(),
            )
        );
    }

    /**
     *
     * @return boolean
     */
    public function isUserCreditLimitReached()
    {
        $userAccountPlan = $this->userAccountPlanService->getForUser($this->user);

        if (!$userAccountPlan->getPlan()->hasConstraintNamed(self::CREDITS_PER_MONTH_CONSTRAINT_NAME)) {
            return false;
        }

        return $this->getCreditsUsedThisMonth() >= $this->getCreditsPerMonthConstraint()->getLimit();
    }

    /**
     * @return AccountPlanConstraint
     */
    public function getFullSiteJobLimitConstraint()
    {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(
            self::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME
        );
    }

    /**
     * @return AccountPlanConstraint
     */
    public function getSingleUrlJobLimitConstraint()
    {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(
            self::SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME
        );
    }

    /**
     * @return AccountPlanConstraint
     */
    public function getJobUrlLimitConstraint()
    {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(
            self::URLS_PER_JOB_CONSTRAINT_NAME
        );
    }

    /**
     * @return AccountPlanConstraint
     */
    public function getCreditsPerMonthConstraint()
    {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(
            self::CREDITS_PER_MONTH_CONSTRAINT_NAME
        );
    }
}