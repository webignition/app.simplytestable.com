<?php
namespace AppBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\CrawlJobContainer;
use AppBundle\Entity\Job\Job;
use AppBundle\Entity\TimePeriod;
use AppBundle\Entity\User;
use AppBundle\Repository\CrawlJobContainerRepository;

class CrawlJobContainerService
{
    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var StateService
     */
    private $stateService;

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
     * @var UrlDiscoveryTaskService
     */
    private $urlDiscoveryTaskService;

    /**
     * @param EntityManagerInterface $entityManager
     * @param JobService $jobService
     * @param StateService $stateService
     * @param JobTypeService $jobTypeService
     * @param UrlDiscoveryTaskService $urlDiscoveryTaskService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        JobService $jobService,
        StateService $stateService,
        JobTypeService $jobTypeService,
        UrlDiscoveryTaskService $urlDiscoveryTaskService
    ) {
        $this->entityManager = $entityManager;
        $this->jobService = $jobService;
        $this->stateService = $stateService;
        $this->jobTypeService = $jobTypeService;
        $this->urlDiscoveryTaskService = $urlDiscoveryTaskService;

        $this->entityRepository = $entityManager->getRepository(CrawlJobContainer::class);
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

        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $crawlJob->setTimePeriod($timePeriod);

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
        $jobStartingState = $this->stateService->get(Job::STATE_STARTING);
        $crawlJobType = $this->jobTypeService->getCrawlType();

        $crawlJob = new Job();
        $crawlJob->setType($crawlJobType);
        $crawlJob->setState($jobStartingState);
        $crawlJob->setUser($job->getUser());
        $crawlJob->setWebsite($job->getWebsite());
        $crawlJob->setParametersString($job->getParametersString());

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