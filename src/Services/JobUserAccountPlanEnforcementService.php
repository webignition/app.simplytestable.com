<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Repository\JobRepository;
use App\Repository\TaskRepository;
use App\Services\Team\Service as TeamService;
use App\Entity\User;
use App\Entity\WebSite;

class JobUserAccountPlanEnforcementService
{
    const FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME = 'full_site_jobs_per_site';
    const SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME = 'single_url_jobs_per_url';
    const URLS_PER_JOB_CONSTRAINT_NAME = 'urls_per_job';
    const CREDITS_PER_MONTH_CONSTRAINT_NAME = 'credits_per_month';

    private $userAccountPlanService;
    private $taskService;
    private $teamService;
    private $stateService;
    private $jobTypeService;
    private $jobRepository;

    /**
     * @var User
     */
    private $user;

    /**
     * @var TaskRepository
     */
    private $taskRepository;


    public function __construct(
        UserAccountPlanService $userAccountPlanService,
        TaskService $taskService,
        TeamService $teamService,
        StateService $stateService,
        JobTypeService $jobTypeService,
        EntityManagerInterface $entityManager,
        JobRepository $jobRepository
    ) {
        $this->userAccountPlanService = $userAccountPlanService;

        $this->taskService = $taskService;
        $this->teamService = $teamService;
        $this->jobTypeService = $jobTypeService;
        $this->stateService = $stateService;
        $this->jobTypeService = $jobTypeService;
        $this->jobRepository = $jobRepository;
        $this->taskRepository = $entityManager->getRepository(Task::class);
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

        $jobType = $this->jobTypeService->get($jobTypeName);
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

        return $this->taskRepository->getCountByUsersAndStatesForPeriod(
            $this->teamService->getPeopleForUser($this->user),
            $this->stateService->getCollection([
                Task::STATE_COMPLETED,
                Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                Task::STATE_FAILED_RETRY_AVAILABLE,
                Task::STATE_FAILED_RETRY_LIMIT_REACHED,
                Task::STATE_SKIPPED,
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
