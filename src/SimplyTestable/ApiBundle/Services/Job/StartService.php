<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Exception\Services\Job\Start\Exception as JobStartServiceException;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Exception\Services\Job\UserAccountPlan\Enforcement\Exception
    as UserAccountPlanEnforcementException;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
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
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var ResqueJobFactory
     */
    private $resqueJobFactory;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @param JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService
     * @param JobTypeService $jobTypeService
     * @param JobService $jobService
     * @param UserService $userService
     * @param ResqueQueueService $resqueQueueService
     * @param StateService $stateService
     * @param UserAccountPlanService $userAccountPlanService
     * @param ResqueJobFactory $resqueJobFactory
     * @param JobRepository $jobRepository
     */
    public function __construct(
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        JobTypeService $jobTypeService,
        JobService $jobService,
        UserService $userService,
        ResqueQueueService $resqueQueueService,
        StateService $stateService,
        UserAccountPlanService $userAccountPlanService,
        ResqueJobFactory $resqueJobFactory,
        JobRepository $jobRepository
    ) {
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->jobTypeService = $jobTypeService;
        $this->jobService = $jobService;
        $this->userService = $userService;
        $this->resqueQueueService = $resqueQueueService;
        $this->stateService = $stateService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->resqueJobFactory = $resqueJobFactory;
        $this->jobRepository = $jobRepository;
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
                $userAccountPlan = $this->userAccountPlanService->getForUser($jobConfiguration->getUser());
                $plan = $userAccountPlan->getPlan();
                $constraint = $plan->getConstraintNamed(
                    JobUserAccountPlanEnforcementService::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME
                );

                throw new UserAccountPlanEnforcementException(
                    'Full site job limit reached for website',
                    UserAccountPlanEnforcementException::CODE_FULL_SITE_JOB_LIMIT_REACHED,
                    $constraint
                );
            }
        }

        if (JobTypeService::SINGLE_URL_NAME == $jobConfiguration->getType()->getName()) {
            $userAccountPlan = $this->userAccountPlanService->getForUser($jobConfiguration->getUser());
            $plan = $userAccountPlan->getPlan();
            $constraint = $plan->getConstraintNamed(
                JobUserAccountPlanEnforcementService::SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME
            );

            if ($this->jobUserAccountPlanEnforcementService->isSingleUrlLimitReachedForWebsite($website)) {
                throw new UserAccountPlanEnforcementException(
                    'Single URL job limit reached for website',
                    UserAccountPlanEnforcementException::CODE_SINGLE_URL_JOB_LIMIT_REACHED,
                    $constraint
                );
            }
        }

        if ($this->jobUserAccountPlanEnforcementService->isUserCreditLimitReached()) {
            $userAccountPlan = $this->userAccountPlanService->getForUser($jobConfiguration->getUser());
            $plan = $userAccountPlan->getPlan();
            $constraint = $plan->getConstraintNamed(
                JobUserAccountPlanEnforcementService::CREDITS_PER_MONTH_CONSTRAINT_NAME
            );

            throw new UserAccountPlanEnforcementException(
                'Credit limit reached',
                UserAccountPlanEnforcementException::CODE_CREDIT_LIMIT_REACHED,
                $constraint
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
            $this->resqueJobFactory->create(
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
        $existingJobs = $this->jobRepository->findBy([
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
