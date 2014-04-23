<?php
namespace SimplyTestable\ApiBundle\Services;

class JobUserAccountPlanEnforcementService {
    
    const FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME = 'full_site_jobs_per_site';
    const SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME = 'single_url_jobs_per_url';
    const URLS_PER_JOB_CONSTRAINT_NAME = 'urls_per_job';
    const CREDITS_PER_MONTH_CONSTRAINT_NAME = 'credits_per_month';
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    private $userAccountPlanService;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Services\JobService 
     */
    private $jobService;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Services\TaskService 
     */
    private $taskService;    
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\User
     */
    private $user;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Type
     */
    private $jobType;
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Services\UserAccountPlanService $userAccountPlanService
     * @param \SimplyTestable\ApiBundle\Services\JobService $jobService
     * @param \SimplyTestable\ApiBundle\Services\TaskService $taskService
     */
    public function __construct(
            \SimplyTestable\ApiBundle\Services\UserAccountPlanService $userAccountPlanService,
            \SimplyTestable\ApiBundle\Services\JobService $jobService,
            \SimplyTestable\ApiBundle\Services\TaskService $taskService) {
        $this->userAccountPlanService = $userAccountPlanService;
        $this->jobService = $jobService;
        $this->taskService = $taskService;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    public function getUserAccountPlanService() {
        return $this->userAccountPlanService;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     */
    public function setUser(\SimplyTestable\ApiBundle\Entity\User $user) {
        $this->user = $user;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Type $jobType
     */
    public function setJobType(\SimplyTestable\ApiBundle\Entity\Job\Type $jobType) {
        $this->jobType = $jobType;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\WebSite $website
     * @return boolean
     */
    public function isFullSiteJobLimitReachedForWebSite(\SimplyTestable\ApiBundle\Entity\WebSite $website) {
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
    
    
    public function isSingleUrlLimitReachedForWebsite(\SimplyTestable\ApiBundle\Entity\WebSite $website) {
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
        return $this->taskService->getEntityRepository()->getCountByUserAndStatesForCurrentMonth(
            $this->user,
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
     * @return \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint
     */
    public function getFullSiteJobLimitConstraint() {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(self::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME);
    }    
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint
     */
    public function getSingleUrlJobLimitConstraint() {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(self::SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME);
    }
    

    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint
     */
    public function getJobUrlLimitConstraint() {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(self::URLS_PER_JOB_CONSTRAINT_NAME);
    }      
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint
     */
    public function getCreditsPerMonthConstraint() {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(self::CREDITS_PER_MONTH_CONSTRAINT_NAME);
    }    
    

}