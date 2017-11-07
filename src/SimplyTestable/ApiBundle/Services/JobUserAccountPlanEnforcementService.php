<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;

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
     * @var StateService
     */
    private $stateService;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @param UserAccountPlanService $userAccountPlanService
     * @param TaskService $taskService
     * @param TeamService $teamService
     * @param JobTypeService $jobTypeService
     * @param StateService $stateService
     * @param JobRepository $jobRepository
     */
    public function __construct(
        UserAccountPlanService $userAccountPlanService,
        TaskService $taskService,
        TeamService $teamService,
        JobTypeService $jobTypeService,
        StateService $stateService,
        JobRepository $jobRepository
    ) {
        $this->userAccountPlanService = $userAccountPlanService;

        $this->taskService = $taskService;
        $this->teamService = $teamService;
        $this->jobTypeService = $jobTypeService;
        $this->stateService = $stateService;
        $this->jobRepository = $jobRepository;
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
        return $this->isJobLimitReachedForWebsite(
            $website,
            self::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME,
            JobTypeService::FULL_SITE_NAME
        );
    }

    /**
     * @param WebSite $website
     *
     * @return bool
     */
    public function isSingleUrlLimitReachedForWebsite(WebSite $website)
    {
        return $this->isJobLimitReachedForWebsite(
            $website,
            self::SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME,
            JobTypeService::SINGLE_URL_NAME
        );
    }

    /**
     * @param WebSite $website
     * @param string $constraintName
     * @param string $jobTypeName
     *
     * @return bool
     */
    private function isJobLimitReachedForWebsite(WebSite $website, $constraintName, $jobTypeName)
    {
        $userAccountPlan = $this->userAccountPlanService->getForUser($this->user);
        $plan = $userAccountPlan->getPlan();

        $constraint = $plan->getConstraintNamed($constraintName);

        if (empty($constraint)) {
            return false;
        }

        $jobType = $this->jobTypeService->getByName($jobTypeName);
        $startDateTime = new \DateTime('first day of this month');
        $endDateTime = new \DateTime('last day of this month');

        $currentCount = $this->jobRepository->getJobCountByUserAndJobTypeAndWebsiteForPeriod(
            $this->user,
            $jobType,
            $website,
            $startDateTime->format('Y-m-01'),
            $endDateTime->format('Y-m-d 23:59:59')
        );

        return $currentCount >= $constraint->getLimit();
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
        $plan = $userAccountPlan->getPlan();

        $constraint = $plan->getConstraintNamed(self::URLS_PER_JOB_CONSTRAINT_NAME);

        if (empty($constraint)) {
            return false;
        }

        return $urlCount > $constraint->getLimit();
    }

    /**
     * @return int
     */
    public function getCreditsUsedThisMonth()
    {
        $startDateTime = new \DateTime('first day of this month');
        $endDateTime = new \DateTime('last day of this month');

        return $this->taskService->getEntityRepository()->getCountByUsersAndStatesForPeriod(
            $this->teamService->getPeopleForUser($this->user),
            $this->stateService->fetchCollection([
                TaskService::COMPLETED_STATE,
                TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
                TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
                TaskService::TASK_SKIPPED_STATE,
            ]),
            $startDateTime->format('Y-m-01'),
            $endDateTime->format('Y-m-d 23:59:59')
        );
    }

    /**
     * @return bool
     */
    public function isUserCreditLimitReached()
    {
        $userAccountPlan = $this->userAccountPlanService->getForUser($this->user);
        $plan = $userAccountPlan->getPlan();

        $constraint = $plan->getConstraintNamed(self::CREDITS_PER_MONTH_CONSTRAINT_NAME);

        if (empty($constraint)) {
            return false;
        }

        return $this->getCreditsUsedThisMonth() >= $constraint->getLimit();
    }
}
