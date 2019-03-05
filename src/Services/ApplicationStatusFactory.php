<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\Job\Job;
use App\Entity\Worker;
use App\Model\ApplicationStatus;
use App\Repository\JobRepository;
use App\Repository\TaskRepository;

class ApplicationStatusFactory
{
    private $applicationStateService;
    private $stateService;
    private $jobRepository;
    private $taskRepository;

    /**
     * @var EntityRepository
     */
    private $workerRepository;

    public function __construct(
        ApplicationStateService $applicationStateService,
        StateService $stateService,
        EntityManagerInterface $entityManager,
        JobRepository $jobRepository,
        TaskRepository $taskRepository
    ) {
        $this->applicationStateService = $applicationStateService;
        $this->stateService = $stateService;

        $this->workerRepository = $entityManager->getRepository(Worker::class);
        $this->jobRepository = $jobRepository;
        $this->taskRepository = $taskRepository;
    }

    public function create(): ApplicationStatus
    {
        $jobInProgressState = $this->stateService->get(Job::STATE_IN_PROGRESS);

        $applicationStatus = new ApplicationStatus(
            $this->applicationStateService->getState(),
            $this->workerRepository->findAll(),
            $this->getLatestGitHash(),
            $this->taskRepository->getThroughputSince(new \DateTime('-1 minute')),
            $this->jobRepository->getCountByState($jobInProgressState)
        );

        return $applicationStatus;
    }

    private function getLatestGitHash(): string
    {
        return trim(shell_exec("git log | head -1 | awk {'print $2;'}"));
    }
}
