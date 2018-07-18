<?php

namespace App\Services\Job;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Job\Configuration as JobConfiguration;
use App\Entity\Job\Job;
use App\Exception\Services\Job\Start\Exception as JobStartServiceException;
use App\Repository\JobRepository;
use App\Resque\Job\Job\ResolveJob;
use App\Services\JobService;
use App\Services\JobTypeService;
use App\Services\JobUserAccountPlanEnforcementService;
use App\Exception\Services\Job\UserAccountPlan\Enforcement\Exception
    as UserAccountPlanEnforcementException;
use webignition\NormalisedUrl\NormalisedUrl;
use App\Services\StateService;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use App\Services\Resque\QueueService as ResqueQueueService;

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
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService
     * @param JobTypeService $jobTypeService
     * @param JobService $jobService
     * @param UserService $userService
     * @param ResqueQueueService $resqueQueueService
     * @param StateService $stateService
     * @param UserAccountPlanService $userAccountPlanService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        JobTypeService $jobTypeService,
        JobService $jobService,
        UserService $userService,
        ResqueQueueService $resqueQueueService,
        StateService $stateService,
        UserAccountPlanService $userAccountPlanService,
        EntityManagerInterface $entityManager
    ) {
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->jobTypeService = $jobTypeService;
        $this->jobService = $jobService;
        $this->userService = $userService;
        $this->resqueQueueService = $resqueQueueService;
        $this->stateService = $stateService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->entityManager = $entityManager;

        $this->jobRepository = $entityManager->getRepository(Job::class);
    }

    /**
     * @param JobConfiguration $jobConfiguration
     * @return null|Job
     * @throws JobStartServiceException
     * @throws UserAccountPlanEnforcementException
     */
    public function start(JobConfiguration $jobConfiguration)
    {
        $jobUrl = new NormalisedUrl($jobConfiguration->getWebsite());
        if (!$jobUrl->isPubliclyRoutable()) {
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

            $this->entityManager->persist($job);
            $this->entityManager->flush();
        }

        $this->resqueQueueService->enqueue(new ResolveJob(['id' => $job->getId()]));

        return $job;
    }

    /**
     * @param JobConfiguration $jobConfiguration
     *
     * @return null|Job
     */
    private function getExistingJob(JobConfiguration $jobConfiguration)
    {
        $incompleteJobStates = $this->stateService->getCollection(
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
