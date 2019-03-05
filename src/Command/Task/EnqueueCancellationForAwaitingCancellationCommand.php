<?php

namespace App\Command\Task;

use App\Repository\TaskRepository;
use App\Entity\Task\Task;
use App\Resque\Job\Task\CancelCollectionJob;
use App\Services\ApplicationStateService;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\StateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnqueueCancellationForAwaitingCancellationCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    private $applicationStateService;
    private $stateService;
    private $resqueQueueService;
    private $taskRepository;

    public function __construct(
        ApplicationStateService $applicationStateService,
        StateService $stateService,
        ResqueQueueService $resqueQueueService,
        TaskRepository $taskRepository,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->stateService = $stateService;
        $this->resqueQueueService = $resqueQueueService;
        $this->taskRepository = $taskRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:enqueue-cancellation-for-awaiting-cancellation')
            ->setDescription('Enqueue resque jobs for cancelling tasks that are awaiting cancellation');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $taskAwaitingCancellationState = $this->stateService->get(Task::STATE_AWAITING_CANCELLATION);

        $taskIds = $this->taskRepository->getIdsByState(
            $taskAwaitingCancellationState
        );

        $output->writeln(count($taskIds).' tasks to enqueue for cancellation');

        if (empty($taskIds)) {
            return self::RETURN_CODE_OK;
        }

        $output->writeln('Enqueuing for cancellation tasks '.  implode(',', $taskIds));
        $this->resqueQueueService->enqueue(new CancelCollectionJob(['ids' => implode(',', $taskIds)]));

        return self::RETURN_CODE_OK;
    }
}
