<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Worker;
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
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        StateService $stateService,
        EntityManagerInterface $entityManager
    ) {
        $this->applicationStateService = $applicationStateService;
        $this->stateService = $stateService;

        $this->workerRepository = $entityManager->getRepository(Worker::class);
        $this->jobRepository = $entityManager->getRepository(Job::class);
        $this->taskRepository = $entityManager->getRepository(Task::class);
    }

    /**
     * @return ApplicationStatus
     */
    public function create()
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

    /**
     * @return string
     */
    private function getLatestGitHash()
    {
        return trim(shell_exec("git log | head -1 | awk {'print $2;'}"));
    }
}
