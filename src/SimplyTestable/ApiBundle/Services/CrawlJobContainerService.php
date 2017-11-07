<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\CrawlJobContainerRepository;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use webignition\NormalisedUrl\NormalisedUrl;

class CrawlJobContainerService
{
    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var JobUserAccountPlanEnforcementService
     */
    private $jobUserAccountPlanEnforcementService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CrawlJobContainerRepository
     */
    private $entityRepository;

    /**
     * @var JobTypeService
     */
    private $jobTypeService;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TaskService $taskService
     * @param TaskTypeService $taskTypeService
     * @param JobService $jobService
     * @param JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService
     * @param StateService $stateService
     * @param UserAccountPlanService $userAccountPlanService
     * @param JobTypeService $jobTypeService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        TaskTypeService $taskTypeService,
        JobService $jobService,
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        StateService $stateService,
        UserAccountPlanService $userAccountPlanService,
        JobTypeService $jobTypeService
    ) {
        $this->entityManager = $entityManager;
        $this->taskService = $taskService;
        $this->taskTypeService = $taskTypeService;
        $this->jobService = $jobService;
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->stateService = $stateService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->jobTypeService = $jobTypeService;

        $this->entityRepository = $entityManager->getRepository(CrawlJobContainer::class);
        $this->taskRepository = $entityManager->getRepository(Task::class);
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function hasForJob(Job $job)
    {
        return $this->entityRepository->hasForJob($job);
    }

    /**
     * @param Job $job
     *
     * @return CrawlJobContainer
     */
    public function getForJob(Job $job)
    {
        if (!$this->hasForJob($job)) {
            return $this->create($job);
        }

        return $this->entityRepository->getForJob($job);
    }

    /**
     * @param CrawlJobContainer $crawlJobContainer
     *
     * @return bool
     */
    public function prepare(CrawlJobContainer $crawlJobContainer)
    {
        $crawlJob = $crawlJobContainer->getCrawlJob();
        $crawlJobTasks = $crawlJob->getTasks();

        if ($crawlJobTasks->count() > 1) {
            return false;
        }

        if ($crawlJobTasks->count() === 1) {
            return true;
        }

        if (JobService::STARTING_STATE !== $crawlJob->getState()->getName()) {
            return false;
        }

        $task = $this->createUrlDiscoveryTask(
            $crawlJobContainer,
            (string)$crawlJobContainer->getParentJob()->getWebsite()
        );

        $jobQueuedState = $this->stateService->get(JobService::QUEUED_STATE);

        $crawlJob->addTask($task);
        $crawlJob->setState($jobQueuedState);

        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $crawlJob->setTimePeriod($timePeriod);

        $this->entityManager->persist($task);
        $this->entityManager->persist($crawlJob);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param CrawlJobContainer $crawlJobContainer
     * @param string $url
     *
     * @return Task
     */
    private function createUrlDiscoveryTask(CrawlJobContainer $crawlJobContainer, $url)
    {
        $parentCanonicalUrl = new NormalisedUrl($crawlJobContainer->getParentJob()->getWebsite()->getCanonicalUrl());

        $scope = array(
            (string)$parentCanonicalUrl
        );

        $hostParts = $parentCanonicalUrl->getHost()->getParts();
        if ($hostParts[0] === 'www') {
            $variant = clone $parentCanonicalUrl;
            $variant->setHost(implode('.', array_slice($parentCanonicalUrl->getHost()->getParts(), 1)));
            $scope[] = (string)$variant;
        } else {
            $variant = new NormalisedUrl($parentCanonicalUrl);
            $variant->setHost('www.' . (string)$variant->getHost());
            $scope[] = (string)$variant;
        }

        $parameters = array(
            'scope' => $scope
        );

        if ($crawlJobContainer->getCrawlJob()->hasParameters()) {
            $parameters = array_merge(
                $parameters,
                json_decode($crawlJobContainer->getCrawlJob()->getParameters(), true)
            );
        }

        $taskQueuedState = $this->stateService->get(TaskService::QUEUED_STATE);

        $task = new Task();
        $task->setJob($crawlJobContainer->getCrawlJob());
        $task->setParameters(json_encode($parameters));
        $task->setState($taskQueuedState);
        $task->setType($this->taskTypeService->getByName('URL discovery'));
        $task->setUrl($url);

        return $task;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function processTaskResults(Task $task)
    {
        if (TaskTypeService::URL_DISCOVERY_TYPE !== $task->getType()->getName()) {
            return false;
        }

        if (TaskService::COMPLETED_STATE !== $task->getState()->getName()) {
            return false;
        }

        if (empty($task->getOutput())) {
            return false;
        }

        if ($task->getOutput()->getErrorCount() > 0) {
            return false;
        }

        /* @var $crawlJobContainer CrawlJobContainer */
        $crawlJobContainer = $this->entityRepository->getForJob($task->getJob());
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $this->jobUserAccountPlanEnforcementService->setUser($crawlJob->getUser());
        $crawlDiscoveredUrlCount = count($this->getDiscoveredUrls($crawlJobContainer));

        if ($this->jobUserAccountPlanEnforcementService->isJobUrlLimitReached($crawlDiscoveredUrlCount)) {
            if ($crawlJob->getAmmendments()->isEmpty()) {
                $userAccountPlan = $this->userAccountPlanService->getForUser($crawlJob->getUser());
                $plan = $userAccountPlan->getPlan();
                $urlsPerJobConstraint = $plan->getConstraintNamed(
                    JobUserAccountPlanEnforcementService::URLS_PER_JOB_CONSTRAINT_NAME
                );

                $this->jobService->addAmmendment(
                    $crawlJob,
                    'plan-url-limit-reached:discovered-url-count-' . $crawlDiscoveredUrlCount,
                    $urlsPerJobConstraint
                );

                $this->entityManager->persist($crawlJob);
                $this->entityManager->flush();
            }

            if (JobService::COMPLETED_STATE !== $crawlJob->getState()->getName()) {
                $this->jobService->cancelIncompleteTasks($crawlJob);
                $this->entityManager->flush();
            }

            return true;
        }

        $taskDiscoveredUrlSet = $this->getDiscoveredUrlsFromTask($task);
        $isFlushRequired = false;

        foreach ($taskDiscoveredUrlSet as $url) {
            if (!$this->isTaskUrl($task->getJob(), $url)) {
                $task = $this->createUrlDiscoveryTask($crawlJobContainer, $url);
                $this->entityManager->persist($task);
                $crawlJob->addTask($task);
                $isFlushRequired = true;
            }
        }

        if ($isFlushRequired) {
            $this->entityManager->persist($crawlJob);
            $this->entityManager->flush();
        }

        return true;
    }

    /**
     * @param Task $task
     *
     * @return string[]
     */
    private function getDiscoveredUrlsFromTask(Task $task)
    {
        $taskDiscoveredUrlSet = json_decode($task->getOutput()->getOutput());

        return is_array($taskDiscoveredUrlSet)
            ? $taskDiscoveredUrlSet
            : array();
    }

    /**
     * @param string $taskOutput
     *
     * @return string[]
     */
    private function getDiscoveredUrlsFromRawTaskOutput($taskOutput)
    {
        $taskDiscoveredUrlSet = json_decode($taskOutput);

        return is_array($taskDiscoveredUrlSet)
            ? $taskDiscoveredUrlSet
            : array();
    }


    /**
     * @param CrawlJobContainer $crawlJobContainer
     *
     * @return string[]
     */
    public function getProcessedUrls(CrawlJobContainer $crawlJobContainer)
    {
        return $this->taskRepository->findUrlsByJobAndState(
            $crawlJobContainer->getCrawlJob(),
            $this->stateService->get(TaskService::COMPLETED_STATE)
        );
    }

    /**
     * @param CrawlJobContainer $crawlJobContainer
     * @param bool $constrainToAccountPlan
     *
     * @return string[]
     */
    public function getDiscoveredUrls(CrawlJobContainer $crawlJobContainer, $constrainToAccountPlan = false)
    {
        $discoveredUrls = array(
            $crawlJobContainer->getParentJob()->getWebsite()->getCanonicalUrl()
        );

        $crawlJob = $crawlJobContainer->getCrawlJob();

        $taskCompletedState = $this->stateService->get(TaskService::COMPLETED_STATE);

        $completedTaskUrls = $this->taskRepository->findUrlsByJobAndState(
            $crawlJob,
            $taskCompletedState
        );

        foreach ($completedTaskUrls as $taskUrl) {
            if (!in_array($taskUrl, $discoveredUrls)) {
                $discoveredUrls[] = $taskUrl;
            }
        }

        $completedTaskOutputs = $this->taskRepository->getOutputCollectionByJobAndState(
            $crawlJob,
            $taskCompletedState
        );

        foreach ($completedTaskOutputs as $taskOutput) {
            $urlSet = $this->getDiscoveredUrlsFromRawTaskOutput($taskOutput);

            foreach ($urlSet as $url) {
                if (!in_array($url, $discoveredUrls)) {
                    $discoveredUrls[] = $url;
                }
            }
        }

        if ($constrainToAccountPlan) {
            $accountPlan = $this->userAccountPlanService->getForUser($crawlJob->getUser())->getPlan();

            $discoveredUrls = array_slice(
                $discoveredUrls,
                0,
                $accountPlan->getConstraintNamed('urls_per_job')->getLimit()
            );
        }

        return $discoveredUrls;
    }

    /**
     * @param Job $job
     * @param string $url
     *
     * @return bool
     */
    private function isTaskUrl(Job $job, $url)
    {
        $url = (string)new NormalisedUrl($url);

        return $this->taskRepository->findUrlExistsByJobAndUrl(
            $job,
            $url
        );
    }

    /**
     * @param Job $job
     *
     * @return CrawlJobContainer
     */
    private function create(Job $job)
    {
        $jobStartingState = $this->stateService->get(JobService::STARTING_STATE);
        $crawlJobType = $this->jobTypeService->getCrawlType();

        $crawlJob = new Job();
        $crawlJob->setType($crawlJobType);
        $crawlJob->setState($jobStartingState);
        $crawlJob->setUser($job->getUser());
        $crawlJob->setWebsite($job->getWebsite());
        $crawlJob->setParameters($job->getParameters());

        $this->entityManager->persist($crawlJob);

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setParentJob($job);
        $crawlJobContainer->setCrawlJob($crawlJob);

        $this->entityManager->persist($crawlJobContainer);
        $this->entityManager->flush();

        return $crawlJobContainer;
    }

    /**
     * @param User $user
     *
     * @return CrawlJobContainer[]
     */
    public function getAllActiveForUser(User $user)
    {
        return $this->entityRepository->getAllForUserByCrawlJobStates(
            $user,
            $this->stateService->getCollection($this->jobService->getIncompleteStateNames())
        );
    }
}
