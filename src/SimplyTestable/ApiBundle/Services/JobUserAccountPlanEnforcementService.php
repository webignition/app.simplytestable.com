<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;

class JobUserAccountPlanEnforcementService {
    
    const FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME = 'full_site_jobs_per_site';
    const SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME = 'single_url_jobs_per_url';
    const URLS_PER_JOB_CONSTRAINT_NAME = 'urls_per_job';
    const CREDITS_PER_MONTH_CONSTRAINT_NAME = 'credits_per_month';
    
    
    /**
     *
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;
    
    
    /**
     *
     * @var JobService
     */
    private $jobService;
    
    
    /**
     *
     * @var TaskService
     */
    private $taskService;    
    
    
    /**
     *
     * @var User
     */
    private $user;
    
    
    /**
     *
     * @var JobType
     */
    private $jobType;


    /**
     * @var TeamService
     */
    private $teamService;


    /**
     * @param UserAccountPlanService $userAccountPlanService
     * @param JobService $jobService
     * @param TaskService $taskService
     * @param TeamService $teamService
     */
    public function __construct(
            UserAccountPlanService $userAccountPlanService,
            JobService $jobService,
            TaskService $taskService,
            TeamService $teamService) {
        $this->userAccountPlanService = $userAccountPlanService;
        $this->jobService = $jobService;
        $this->taskService = $taskService;
        $this->teamService = $teamService;
    }
    
    
    /**
     * 
     * @return UserAccountPlanService
     */
    public function getUserAccountPlanService() {
        return $this->userAccountPlanService;
    }
    
    
    /**
     * 
     * @param User $user
     */
    public function setUser(User $user) {
        $this->user = $user;
    }
    
    
    /**
     * 
     * @param JobType $jobType
     */
    public function setJobType(JobType $jobType) {
        $this->jobType = $jobType;
    }
    
    
    /**
     * 
     * @param WebSite $website
     * @return boolean
     */
    public function isFullSiteJobLimitReachedForWebSite(WebSite $website) {
        $userAccountPlan = $this->userAccountPlanService->getForUser($this->user);

        if (!$userAccountPlan->getPlan()->hasConstraintNamed(self::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME)) {
            return false;
        }
        
        $currentCount = $this->jobService->getEntityRepository()->getJobCountByUserAndJobTypeAndWebsiteForCurrentMonth($this->user, $this->jobType, $website);        
        if ($currentCount === 0) {
            return false;
        }
        
        return $currentCount >= $userAccountPlan->getPlan()->getConstraintNamed(self::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME)->getLimit();
    }
    
    
    public function isSingleUrlLimitReachedForWebsite(WebSite $website) {
        $userAccountPlan = $this->userAccountPlanService->getForUser($this->user);

        if (!$userAccountPlan->getPlan()->hasConstraintNamed(self::SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME)) {
            return false;
        }
        
        $currentCount = $this->jobService->getEntityRepository()->getJobCountByUserAndJobTypeAndWebsiteForCurrentMonth($this->user, $this->jobType, $website);
        if ($currentCount === 0) {
            return false;
        }
        
        return $currentCount >= $userAccountPlan->getPlan()->getConstraintNamed(self::SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME)->getLimit();
    }
    
    
    public function isJobUrlLimitReached($urlCount) {
        if ($urlCount === 0) {
            return false;
        }
        
        $userAccountPlan = $this->userAccountPlanService->getForUser($this->user);

        if (!$userAccountPlan->getPlan()->hasConstraintNamed(self::URLS_PER_JOB_CONSTRAINT_NAME)) {
            return false;
        }
        
        return $urlCount > $userAccountPlan->getPlan()->getConstraintNamed(self::URLS_PER_JOB_CONSTRAINT_NAME)->getLimit();        
    }
    
    
    /**
     * 
     * @return int
     */
    public function getCreditsUsedThisMonth() {
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
    public function isUserCreditLimitReached() {        
        $userAccountPlan = $this->userAccountPlanService->getForUser($this->user);

        if (!$userAccountPlan->getPlan()->hasConstraintNamed(self::CREDITS_PER_MONTH_CONSTRAINT_NAME)) {
            return false;
        }
        
        return $this->getCreditsUsedThisMonth() >= $userAccountPlan->getPlan()->getConstraintNamed(self::CREDITS_PER_MONTH_CONSTRAINT_NAME)->getLimit();               
    }
    

    /**
     * 
     * @return AccountPlanConstraint
     */
    public function getFullSiteJobLimitConstraint() {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(self::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME);
    }    
    
    
    /**
     * 
     * @return AccountPlanConstraint
     */
    public function getSingleUrlJobLimitConstraint() {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(self::SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME);
    }
    

    /**
     * 
     * @return AccountPlanConstraint
     */
    public function getJobUrlLimitConstraint() {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(self::URLS_PER_JOB_CONSTRAINT_NAME);
    }      
    
    
    /**
     * 
     * @return AccountPlanConstraint
     */
    public function getCreditsPerMonthConstraint() {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(self::CREDITS_PER_MONTH_CONSTRAINT_NAME);
    }    
    

}