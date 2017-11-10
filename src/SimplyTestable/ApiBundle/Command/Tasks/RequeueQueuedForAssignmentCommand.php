<?php
namespace SimplyTestable\ApiBundle\Command\Tasks;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Command\BaseCommand;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RequeueQueuedForAssignmentCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    const NAME = 'simplytestable:tasks:requeue-queued-for-assignment';
    const DESCRIPTION = 'Change the state of all "queued-for-assignment" tasks to "queued"';

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param StateService $stateService
     * @param EntityManager $entityManager
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        StateService $stateService,
        EntityManager $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->stateService = $stateService;
        $this->entityManager = $entityManager;

        $this->taskRepository = $entityManager->getRepository(Task::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription(self::DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $isInMaintenanceReadOnlyState = $this->applicationStateService->isInMaintenanceReadOnlyState();
        $isInMaintenanceBackupReadOnlyState = $this->applicationStateService->isInMaintenanceBackupReadOnlyState();

        $isInReadOnlyState = $isInMaintenanceReadOnlyState || $isInMaintenanceBackupReadOnlyState;

        if ($isInReadOnlyState) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $taskQueuedForAssignmentState = $this->stateService->get(TaskService::QUEUED_FOR_ASSIGNMENT_STATE);

        /* @var Task[] $tasks */
        $tasks = $this->taskRepository->findBy([
            'state' => $taskQueuedForAssignmentState,
        ]);

        if (empty($tasks)) {
            return self::RETURN_CODE_OK;
        }

        $taskQueuedState = $this->stateService->get(TaskService::QUEUED_STATE);

        foreach ($tasks as $task) {
            $task->setState($taskQueuedState);
            $this->entityManager->persist($task);
        }

        $this->entityManager->flush();

        return self::RETURN_CODE_OK;
    }
}
