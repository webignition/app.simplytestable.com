<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Exception\Services\Job\Start\Exception as JobStartServiceException;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Exception\Services\Job\UserAccountPlan\Enforcement\Exception as UserAccountPlanEnforcementException;

class StartService {

    /**
     * @var JobUserAccountPlanEnforcementService
     */
    private $jobUserAccountPlanEnforcementService;

    /**
     * @var JobTypeService
     */
    private $jobTypeService;


    public function __construct(
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        JobTypeService $jobTypeService
    ) {
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->jobTypeService = $jobTypeService;
    }


    public function start(JobConfiguration $jobConfiguration) {
        if (!$jobConfiguration->getWebsite()->isPubliclyRoutable()) {
            throw new JobStartServiceException(
                'Unroutable website',
                JobStartServiceException::CODE_UNROUTABLE_WEBSITE
            );
        }

        $this->jobUserAccountPlanEnforcementService->setUser($jobConfiguration->getUser());
        $this->jobUserAccountPlanEnforcementService->setJobType($jobConfiguration->getType());

        if ($jobConfiguration->getType()->equals($this->jobTypeService->getFullSiteType())) {
            if ($this->jobUserAccountPlanEnforcementService->isFullSiteJobLimitReachedForWebSite($jobConfiguration->getWebsite())) {
                throw new UserAccountPlanEnforcementException(
                    'Full site job limit reached for website',
                    UserAccountPlanEnforcementException::CODE_FULL_SITE_JOB_LIMIT_REACHED,
                    $this->jobUserAccountPlanEnforcementService->getFullSiteJobLimitConstraint()
                );
            }
        }

        if ($jobConfiguration->getType()->equals($this->jobTypeService->getSingleUrlType())) {
            if ($this->jobUserAccountPlanEnforcementService->isSingleUrlLimitReachedForWebsite($jobConfiguration->getWebsite())) {
                throw new UserAccountPlanEnforcementException(
                    'Single URL job limit reached for website',
                    UserAccountPlanEnforcementException::CODE_SINGLE_URL_JOB_LIMIT_REACHED,
                    $this->jobUserAccountPlanEnforcementService->getSingleUrlJobLimitConstraint()
                );
            }
        }
    }



}