<?php
namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Entity\Worker;
use App\Model\ApplicationStatus;
use App\Repository\JobRepository;
use App\Repository\TaskRepository;

class ApplicationStatusFactory
{
    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var EntityRepository
     */
    private $workerRepository;

    private $jobRepository;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    public function __construct(
        ApplicationStateService $applicationStateService,
        StateService $stateService,
        EntityManagerInterface $entityManager,
        JobRepository $jobRepository
    ) {
        $this->applicationStateService = $applicationStateService;
        $this->stateService = $stateService;

        $this->workerRepository = $entityManager->getRepository(Worker::class);
        $this->jobRepository = $jobRepository;
        $this->taskRepository = $entityManager->getRepository(Task::class);
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
