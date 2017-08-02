<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\CrawlJobContainerRepository;
use webignition\NormalisedUrl\NormalisedUrl;

class CrawlJobContainerService extends EntityService
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
     * @var JobTypeService
     */
    private $jobTypeService;

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
     * @param EntityManager $entityManager
     * @param TaskService $taskService
     * @param TaskTypeService $taskTypeService
     * @param JobTypeService $jobTypeService
     * @param JobService $jobService
     * @param JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService
     * @param StateService $stateService
     */
    public function __construct(
        EntityManager $entityManager,
        TaskService $taskService,
        TaskTypeService $taskTypeService,
        JobTypeService $jobTypeService,
        JobService $jobService,
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        StateService $stateService
    ) {
        parent::__construct($entityManager);
        $this->taskService = $taskService;
        $this->taskTypeService = $taskTypeService;
        $this->jobTypeService = $jobTypeService;
        $this->jobService = $jobService;
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->stateService = $stateService;
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return CrawlJobContainer::class;
    }

    /**
     * @param int $id
     *
     * @return Job
     */
    public function getById($id)
    {
        return $this->getEntityRepository()->find($id);
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function hasForJob(Job $job)
    {
        return $this->getEntityRepository()->hasForJob($job);
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

        return $this->getEntityRepository()->getForJob($job);
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

        $jobQueuedState = $this->stateService->fetch(JobService::QUEUED_STATE);

        $crawlJob->addTask($task);
        $crawlJob->setState($jobQueuedState);

        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $crawlJob->setTimePeriod($timePeriod);

        $this->getManager()->persist($task);
        $this->getManager()->persist($crawlJob);
        $this->getManager()->flush();

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

        $task = new Task();
        $task->setJob($crawlJobContainer->getCrawlJob());
        $task->setParameters(json_encode($parameters));
        $task->setState($this->taskService->getQueuedState());
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
        if (is_null($task->getType())) {
            return false;
        }

        if (!$task->getType()->equals($this->taskTypeService->getByName('URL discovery'))) {
            return false;
        }

        if (is_null($task->getState())) {
            return false;
        }

        if (!$task->getState()->equals($this->taskService->getCompletedState())) {
            return false;
        }

        if (is_null($task->getOutput())) {
            return false;
        }

        if ($task->getOutput()->getErrorCount() > 0) {
            return false;
        }

        /* @var $crawlJobContainer CrawlJobContainer */
        $crawlJobContainer = $this->getEntityRepository()->findOneBy(array(
            'crawlJob' => $task->getJob()
        ));

        $taskDiscoveredUrlSet = $this->getDiscoveredUrlsFromTask($task);
        if (!count($taskDiscoveredUrlSet) === 0) {
            return true;
        }

        $crawlJob = $crawlJobContainer->getCrawlJob();

        $this->jobUserAccountPlanEnforcementService->setUser($crawlJob->getUser());
        $crawlDiscoveredUrlCount = count($this->getDiscoveredUrls($crawlJobContainer));

        if ($this->jobUserAccountPlanEnforcementService->isJobUrlLimitReached($crawlDiscoveredUrlCount)) {
            if ($crawlJob->getAmmendments()->isEmpty()) {
                $this->jobService->addAmmendment(
                    $crawlJob,
                    'plan-url-limit-reached:discovered-url-count-' . $crawlDiscoveredUrlCount,
                    $this->jobUserAccountPlanEnforcementService->getJobUrlLimitConstraint()
                );
                $this->jobService->persistAndFlush($crawlJob);
            }

            if (JobService::COMPLETED_STATE !== $crawlJob->getState()->getName()) {
                $this->jobService->cancelIncompleteTasks($crawlJob);
                $this->taskService->getManager()->flush();
            }

            return true;
        }

        $isFlushRequired = false;

        foreach ($taskDiscoveredUrlSet as $url) {
            if (!$this->isTaskUrl($task->getJob(), $url)) {
                $task = $this->createUrlDiscoveryTask($crawlJobContainer, $url);
                $this->getManager()->persist($task);
                $crawlJob->addTask($task);
                $isFlushRequired = true;
            }
        }

        if ($isFlushRequired) {
            $this->getManager()->persist($crawlJob);
            $this->getManager()->flush();
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
        return $this->taskService->getEntityRepository()->findUrlsByJobAndState(
            $crawlJobContainer->getCrawlJob(),
            $this->taskService->getCompletedState()
        );
    }

    /**
     * @param CrawlJobContainer $crawlJobContainer
     *
     * @return string[]
     */
    public function getDiscoveredUrls(CrawlJobContainer $crawlJobContainer, $constrainToAccountPlan = false)
    {
        $discoveredUrls = array(
            $crawlJobContainer->getParentJob()->getWebsite()->getCanonicalUrl()
        );

        $completedTaskUrls = $this->taskService->getEntityRepository()->findUrlsByJobAndState(
            $crawlJobContainer->getCrawlJob(),
            $this->taskService->getCompletedState()
        );

        foreach ($completedTaskUrls as $taskUrl) {
            if (!in_array($taskUrl, $discoveredUrls)) {
                $discoveredUrls[] = $taskUrl;
            }
        }

        $completedTaskOutputs = $this->taskService->getEntityRepository()->getOutputCollectionByJobAndState(
            $crawlJobContainer->getCrawlJob(),
            $this->taskService->getCompletedState()
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
            $accountPlan = $this->jobUserAccountPlanEnforcementService->getUserAccountPlanService()->getForUser(
                $crawlJobContainer->getCrawlJob()->getUser()
            )->getPlan();
            if ($accountPlan->hasConstraintNamed('urls_per_job')) {
                return array_slice($discoveredUrls, 0, $accountPlan->getConstraintNamed('urls_per_job')->getLimit());
            }
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

        return $this->taskService->getEntityRepository()->findUrlExistsByJobAndUrl(
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
        $jobStartingState = $this->stateService->fetch(JobService::STARTING_STATE);

        $crawlJob = new Job();
        $crawlJob->setType($this->jobTypeService->getCrawlType());
        $crawlJob->setState($jobStartingState);
        $crawlJob->setUser($job->getUser());
        $crawlJob->setWebsite($job->getWebsite());
        $crawlJob->setParameters($job->getParameters());

        $this->getManager()->persist($crawlJob);

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setParentJob($job);
        $crawlJobContainer->setCrawlJob($crawlJob);

        return $this->persistAndFlush($crawlJobContainer);
    }

    /**
     * @param CrawlJobContainer $crawlJobContainer
     *
     * @return CrawlJobContainer
     */
    public function persistAndFlush(CrawlJobContainer $crawlJobContainer)
    {
        $this->getManager()->persist($crawlJobContainer);
        $this->getManager()->flush();

        return $crawlJobContainer;
    }

    /**
     * @param User $user
     *
     * @return CrawlJobContainer[]
     */
    public function getAllActiveForUser(User $user)
    {
        /* @var CrawlJobContainerRepository $entityRepository */
        $entityRepository = $this->getEntityRepository();

        return $entityRepository->getAllForUserByCrawlJobStates(
            $user,
            $this->jobService->getIncompleteStates()
        );
    }
}
