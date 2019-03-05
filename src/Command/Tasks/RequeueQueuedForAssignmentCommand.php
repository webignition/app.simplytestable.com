<?php

namespace App\Command\Tasks;

use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Services\ApplicationStateService;
use App\Services\StateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RequeueQueuedForAssignmentCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    const NAME = 'simplytestable:tasks:requeue-queued-for-assignment';
    const DESCRIPTION = 'Change the state of all "queued-for-assignment" tasks to "queued"';

    private $applicationStateService;
    private $stateService;
    private $entityManager;
    private $taskRepository;

    public function __construct(
        ApplicationStateService $applicationStateService,
        StateService $stateService,
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->stateService = $stateService;
        $this->entityManager = $entityManager;
        $this->taskRepository = $taskRepository;
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
        if ($this->applicationStateService->isInReadOnlyMode()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $taskQueuedForAssignmentState = $this->stateService->get(Task::STATE_QUEUED_FOR_ASSIGNMENT);

        /* @var Task[] $tasks */
        $tasks = $this->taskRepository->findBy([
            'state' => $taskQueuedForAssignmentState,
        ]);

        if (empty($tasks)) {
            return self::RETURN_CODE_OK;
        }

        $taskQueuedState = $this->stateService->get(Task::STATE_QUEUED);

        foreach ($tasks as $task) {
            $task->setState($taskQueuedState);
            $this->entityManager->persist($task);
        }

        $this->entityManager->flush();

        return self::RETURN_CODE_OK;
    }
}
