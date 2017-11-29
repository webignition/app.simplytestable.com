<?php
namespace SimplyTestable\ApiBundle\Services\TaskPostProcessor;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\CrawlJobUrlCollector;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\UrlDiscoveryTaskService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use webignition\NormalisedUrl\NormalisedUrl;

class UrlDiscoveryTaskPostProcessor implements TaskPostProcessorInterface
{
    /**
     * @var CrawlJobContainerService
     */
    private $crawlJobContainerService;

    /**
     * @var CrawlJobUrlCollector
     */
    private $crawlJobUrlCollector;

    /**
     * @var JobUserAccountPlanEnforcementService
     */
    private $jobUserAccountPlanEnforcementService;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var UrlDiscoveryTaskService
     */
    private $urlDiscoveryTaskService;

    /**
     * @param CrawlJobContainerService $crawlJobContainerService
     * @param CrawlJobUrlCollector $crawlJobUrlCollector
     * @param JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService
     * @param UserAccountPlanService $userAccountPlanService
     * @param JobService $jobService
     * @param EntityManagerInterface $entityManager
     * @param UrlDiscoveryTaskService $urlDiscoveryTaskService
     */
    public function __construct(
        CrawlJobContainerService $crawlJobContainerService,
        CrawlJobUrlCollector $crawlJobUrlCollector,
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        UserAccountPlanService $userAccountPlanService,
        JobService $jobService,
        EntityManagerInterface $entityManager,
        UrlDiscoveryTaskService $urlDiscoveryTaskService
    ) {
        $this->crawlJobContainerService = $crawlJobContainerService;
        $this->crawlJobUrlCollector = $crawlJobUrlCollector;
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->jobService = $jobService;
        $this->entityManager = $entityManager;
        $this->urlDiscoveryTaskService = $urlDiscoveryTaskService;

        $this->taskRepository = $entityManager->getRepository(Task::class);
    }

    /**
     * @param Type $taskType
     *
     * @return bool
     */
    public function handles(Type $taskType)
    {
        return $taskType->getName() === TaskTypeService::URL_DISCOVERY_TYPE;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function process(Task $task)
    {
        if (TaskService::COMPLETED_STATE !== $task->getState()->getName()) {
            return false;
        }

        if (empty($task->getOutput())) {
            return false;
        }

        if ($task->getOutput()->getErrorCount() > 0) {
            return false;
        }

        $job = $task->getJob();
        $crawlJobContainer = $this->crawlJobContainerService->getForJob($job);
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $discoveredUrls = $this->crawlJobUrlCollector->getDiscoveredUrls($crawlJobContainer);
        $crawlDiscoveredUrlCount = count($discoveredUrls);

        $user = $job->getUser();
        $this->jobUserAccountPlanEnforcementService->setUser($user);

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

            if (Job::STATE_COMPLETED !== $crawlJob->getState()->getName()) {
                $this->jobService->cancelIncompleteTasks($crawlJob);
                $this->entityManager->flush();
            }

            return true;
        }

        $taskDiscoveredUrlSet = json_decode($task->getOutput()->getOutput());

        if (!is_array($taskDiscoveredUrlSet)) {
            $taskDiscoveredUrlSet = [];
        }
        $isFlushRequired = false;

        foreach ($taskDiscoveredUrlSet as $url) {
            $isUrlUsedByTask = $this->taskRepository->findUrlExistsByJobAndUrl(
                $job,
                (string)new NormalisedUrl($url)
            );

            if (!$isUrlUsedByTask) {
                $task = $this->urlDiscoveryTaskService->create(
                    $crawlJob,
                    $crawlJobContainer->getParentJob()->getWebsite()->getCanonicalUrl(),
                    $url
                );

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
}
