<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Model\ApplicationStatus;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Repository\TaskRepository;

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

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param StateService $stateService
     * @param EntityManagerInterface $entityManager
     * @param TaskRepository $taskRepository
     * @param EntityRepository $workerRepository
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        StateService $stateService,
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository,
        EntityRepository $workerRepository
    ) {
        $this->applicationStateService = $applicationStateService;
        $this->stateService = $stateService;

        $this->workerRepository = $workerRepository;
        $this->jobRepository = $entityManager->getRepository(Job::class);
        $this->taskRepository = $taskRepository;
    }

    /**
     * @return ApplicationStatus
     */
    public function create()
    {
        $jobInProgressState = $this->stateService->fetch(JobService::IN_PROGRESS_STATE);

        $applicationStatus = new ApplicationStatus(
            $this->applicationStateService->getState(),
            $this->workerRepository->findAll(),
            $this->getLatestGitHash(),
            $this->taskRepository->getThroughputSince(new \DateTime('-1 minute')),
            $this->jobRepository->getCountByState($jobInProgressState)
        );

        return $applicationStatus;
    }

    /**
     * @return string
     */
    private function getLatestGitHash()
    {
        return trim(shell_exec("git log | head -1 | awk {'print $2;'}"));
    }
}
