<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Exception\Services\Job\Start\Exception as JobStartServiceException;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Exception\Services\Job\UserAccountPlan\Enforcement\Exception
    as UserAccountPlanEnforcementException;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;

class StartService
{
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

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @param JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService
     * @param JobTypeService $jobTypeService
     * @param JobService $jobService
     * @param UserService $userService
     * @param ResqueQueueService $resqueQueueService
     * @param StateService $stateService
     */
    public function __construct(
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        JobTypeService $jobTypeService,
        JobService $jobService,
        UserService $userService,
        ResqueQueueService $resqueQueueService,
        StateService $stateService
    ) {
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->jobTypeService = $jobTypeService;
        $this->jobService = $jobService;
        $this->userService = $userService;
        $this->resqueQueueService = $resqueQueueService;
        $this->stateService = $stateService;
    }

    /**
     * @param JobConfiguration $jobConfiguration
     * @return null|Job
     * @throws JobStartServiceException
     * @throws UserAccountPlanEnforcementException
     */
    public function start(JobConfiguration $jobConfiguration)
    {
        if (!$jobConfiguration->getWebsite()->isPubliclyRoutable()) {
            throw new JobStartServiceException(
                'Unroutable website',
                JobStartServiceException::CODE_UNROUTABLE_WEBSITE
            );
        }

        $this->jobUserAccountPlanEnforcementService->setUser($jobConfiguration->getUser());

        $website = $jobConfiguration->getWebsite();

        if (JobTypeService::FULL_SITE_NAME == $jobConfiguration->getType()->getName()) {
            if ($this->jobUserAccountPlanEnforcementService->isFullSiteJobLimitReachedForWebSite($website)) {
                throw new UserAccountPlanEnforcementException(
                    'Full site job limit reached for website',
                    UserAccountPlanEnforcementException::CODE_FULL_SITE_JOB_LIMIT_REACHED,
                    $this->jobUserAccountPlanEnforcementService->getFullSiteJobLimitConstraint()
                );
            }
        }

        if (JobTypeService::SINGLE_URL_NAME == $jobConfiguration->getType()->getName()) {
            if ($this->jobUserAccountPlanEnforcementService->isSingleUrlLimitReachedForWebsite($website)) {
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

        $existingJob = $this->getExistingJob($jobConfiguration);
        if (!empty($existingJob)) {
            return $existingJob;
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
     *
     * @return null|Job
     */
    private function getExistingJob(JobConfiguration $jobConfiguration)
    {
        $incompleteJobStates = $this->stateService->fetchCollection(
            $this->jobService->getIncompleteStateNames()
        );

        /* @var $existingJob Job */
        $existingJobs = $this->jobService->getEntityRepository()->findBy([
            'website' => $jobConfiguration->getWebsite(),
            'state' => $incompleteJobStates,
            'user' => $jobConfiguration->getUser(),
            'type' => $jobConfiguration->getType()
        ]);

        $jobConfigurationTaskTypes = $jobConfiguration->getTaskConfigurationsAsCollection()->getTaskTypes();

        foreach ($existingJobs as $existingJob) {
            if ($existingJob->getTaskTypeCollection()->equals($jobConfigurationTaskTypes)) {
                return $existingJob;
            }
        }

        return null;
    }
}
