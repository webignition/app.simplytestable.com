<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\CrawlJobContainer;
use App\Entity\Job\Job;
use App\Entity\User;
use App\Repository\CrawlJobContainerRepository;

class CrawlJobContainerService
{
    private $jobService;
    private $stateService;
    private $entityManager;
    private $crawlJobContainerRepository;
    private $jobTypeService;
    private $urlDiscoveryTaskService;

    public function __construct(
        EntityManagerInterface $entityManager,
        JobService $jobService,
        StateService $stateService,
        JobTypeService $jobTypeService,
        UrlDiscoveryTaskService $urlDiscoveryTaskService,
        CrawlJobContainerRepository $crawlJobContainerRepository
    ) {
        $this->entityManager = $entityManager;
        $this->jobService = $jobService;
        $this->stateService = $stateService;
        $this->jobTypeService = $jobTypeService;
        $this->urlDiscoveryTaskService = $urlDiscoveryTaskService;
        $this->crawlJobContainerRepository = $crawlJobContainerRepository;
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function hasForJob(Job $job)
    {
        return $this->crawlJobContainerRepository->hasForJob($job);
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

        return $this->crawlJobContainerRepository->getForJob($job);
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

        if (Job::STATE_STARTING !== $crawlJob->getState()->getName()) {
            return false;
        }

        $task = $this->urlDiscoveryTaskService->create(
            $crawlJob,
            $crawlJobContainer->getParentJob()->getWebsite()->getCanonicalUrl(),
            (string)$crawlJobContainer->getParentJob()->getWebsite()
        );

        $jobQueuedState = $this->stateService->get(Job::STATE_QUEUED);

        $crawlJob->addTask($task);
        $crawlJob->setState($jobQueuedState);
        $crawlJob->setStartDateTime(new \DateTime());

        $this->entityManager->persist($task);
        $this->entityManager->persist($crawlJob);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param Job $job
     *
     * @return CrawlJobContainer
     */
    private function create(Job $job)
    {
        $crawlJob = Job::create(
            $job->getUser(),
            $job->getWebsite(),
            $this->jobTypeService->getCrawlType(),
            $this->stateService->get(Job::STATE_STARTING),
            $job->getParametersString()
        );

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
        return $this->crawlJobContainerRepository->getAllForUserByCrawlJobStates(
            $user,
            $this->stateService->getCollection($this->jobService->getIncompleteStateNames())
        );
    }
}
