<?php

namespace App\Services;

use App\Entity\CrawlJobContainer;
use App\Entity\Task\Task;
use App\Repository\TaskRepository;

class CrawlJobUrlCollector
{
    private $stateService;
    private $userAccountPlanService;
    private $taskRepository;

    /**
     * @var bool
     */
    private $constrainToAccountPlan;

    public function __construct(
        StateService $stateService,
        UserAccountPlanService $userAccountPlanService,
        TaskRepository $taskRepository
    ) {
        $this->stateService = $stateService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->taskRepository = $taskRepository;
    }

    /**
     * @param bool $constrainToAccountPlan
     */
    public function setConstrainToAccountPlan($constrainToAccountPlan)
    {
        $this->constrainToAccountPlan = $constrainToAccountPlan;
    }

    /**
     * @param CrawlJobContainer $crawlJobContainer
     *
     * @return string[]
     */
    public function getDiscoveredUrls(CrawlJobContainer $crawlJobContainer)
    {
        $parentJob = $crawlJobContainer->getParentJob();
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $website = $parentJob->getWebsite();

        $discoveredUrls = [
            (string)$website->getCanonicalUrl(),
        ];

        $taskCompletedState = $this->stateService->get(Task::STATE_COMPLETED);

        $completedTaskUrls = $this->taskRepository->findUrlsByJobAndState(
            $crawlJob,
            $taskCompletedState
        );

        $discoveredUrls = array_merge($discoveredUrls, $completedTaskUrls);

        $completedTaskOutputs = $this->taskRepository->getOutputCollectionByJobAndState(
            $crawlJob,
            $taskCompletedState
        );

        foreach ($completedTaskOutputs as $taskOutput) {
            $discoveredUrlsForTask = $taskDiscoveredUrlSet = json_decode($taskOutput);

            if (is_array($discoveredUrlsForTask)) {
                $discoveredUrls = array_merge($discoveredUrls, $discoveredUrlsForTask);
            }
        }

        $discoveredUrls = array_values(array_unique($discoveredUrls));

        if ($this->constrainToAccountPlan) {
            $accountPlan = $this->userAccountPlanService->getForUser($crawlJob->getUser())->getPlan();

            $discoveredUrls = array_slice(
                $discoveredUrls,
                0,
                $accountPlan->getConstraintNamed('urls_per_job')->getLimit()
            );
        }

        return $discoveredUrls;
    }
}
