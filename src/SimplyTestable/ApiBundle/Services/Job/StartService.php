<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Exception\Services\Job\Start\Exception as JobStartServiceException;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Exception\Services\Job\UserAccountPlan\Enforcement\Exception as UserAccountPlanEnforcementException;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;

class StartService {

    /**
     * @var JobUserAccountPlanEnforcementService
     */
    private $jobUserAccountPlanEnforcementService;

    /**
     * @var JobTypeService
     */
    private $jobTypeService;


    /**
     * @var JobService
     */
    private $jobService;


    /**
     * @var UserService
     */
    private $userService;


    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;


    public function __construct(
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        JobTypeService $jobTypeService,
        JobService $jobService,
        UserService $userService,
        ResqueQueueService $resqueQueueService
    ) {
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->jobTypeService = $jobTypeService;
        $this->jobService = $jobService;
        $this->userService = $userService;
        $this->resqueQueueService = $resqueQueueService;
    }


    /**
     * @param JobConfiguration $jobConfiguration
     * @return null|Job
     * @throws JobStartServiceException
     * @throws UserAccountPlanEnforcementException
     */
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

        if ($this->jobUserAccountPlanEnforcementService->isUserCreditLimitReached()) {
            throw new UserAccountPlanEnforcementException(
                'Credit limit reached',
                UserAccountPlanEnforcementException::CODE_CREDIT_LIMIT_REACHED,
                $this->jobUserAccountPlanEnforcementService->getCreditsPerMonthConstraint()
            );
        }

        if ($this->hasExistingJob($jobConfiguration)) {
            return $this->getExistingJob($jobConfiguration);
        }

        $job = $this->jobService->create(
            $jobConfiguration
        );

        if ($this->userService->isPublicUser($jobConfiguration->getUser())) {
            $job->setIsPublic(true);
            $this->jobService->persistAndFlush($job);
        }

        $this->resqueQueueService->enqueue(
            $this->resqueQueueService->getJobFactoryService()->create(
                'job-resolve',
                ['id' => $job->getId()]
            )
        );

        return $job;
    }


    /**
     * @param JobConfiguration $jobConfiguration
     * @return null|Job
     */
    public function getExistingJob(JobConfiguration $jobConfiguration) {
        /* @var $existingJob Job */
        $existingJobs = $this->jobService->getEntityRepository()->findBy([
            'website' => $jobConfiguration->getWebsite(),
            'state' => $this->jobService->getIncompleteStates(),
            'user' => $jobConfiguration->getUser(),
            'type' => $jobConfiguration->getType()
        ]);

        foreach ($existingJobs as $existingJob) {
            if ($existingJob->getTaskTypeCollection()->equals($jobConfiguration->getTaskConfigurationsAsCollection()->getTaskTypes())) {
                return $existingJob;
            }
        }

        return null;
    }


    /**
     * @param JobConfiguration $jobConfiguration
     * @return bool
     */
    public function hasExistingJob(JobConfiguration $jobConfiguration) {
        return !is_null($this->getExistingJob($jobConfiguration));
    }



}