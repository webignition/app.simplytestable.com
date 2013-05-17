<?php
namespace SimplyTestable\ApiBundle\Services;

class JobUserAccountPlanEnforcementService {
    
    const FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME = 'full_site_jobs_per_site';
    
    
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
     * @var SimplyTestable\ApiBundle\Entity\User
     */
    private $user;
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Services\UserAccountPlanService $userAccountPlanService
     * @param \SimplyTestable\ApiBundle\Services\JobService $jobService
     */
    public function __construct(
            \SimplyTestable\ApiBundle\Services\UserAccountPlanService $userAccountPlanService,
            \SimplyTestable\ApiBundle\Services\JobService $jobService) {
        $this->userAccountPlanService = $userAccountPlanService;
        $this->jobService = $jobService;
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
     * @param \SimplyTestable\ApiBundle\Entity\WebSite $website
     * @return boolean
     */
    public function isFullSiteJobLimitReachedForWebSite(\SimplyTestable\ApiBundle\Entity\WebSite $website) {
        $userAccountPlan = $this->userAccountPlanService->getForUser($this->user);

        if (!$userAccountPlan->getPlan()->hasConstraintNamed(self::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME)) {
            return false;
        }
        
        $currentCount = $this->jobService->getEntityRepository()->getJobCountByUserAndWebsiteForCurrentMonth($this->user, $website);        
        if ($currentCount === 0) {
            return false;
        }
        
        return $currentCount >= $userAccountPlan->getPlan()->getConstraintNamed(self::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME)->getLimit();
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint
     */
    public function getFullSiteJobLimitConstraint() {
        return $this->userAccountPlanService->getForUser($this->user)->getPlan()->getConstraintNamed(self::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME);
    }
    

}