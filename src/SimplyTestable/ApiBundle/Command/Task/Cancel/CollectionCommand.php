<?php
namespace SimplyTestable\ApiBundle\Command\Task\Cancel;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\WorkerTaskCancellationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectionCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var WorkerTaskCancellationService
     */
    private $workerTaskCancellationService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param TaskService $taskService
     * @param WorkerTaskCancellationService $workerTaskCancellationService
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        TaskService $taskService,
        WorkerTaskCancellationService $workerTaskCancellationService,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->taskService = $taskService;
        $this->workerTaskCancellationService = $workerTaskCancellationService;
        $this->logger = $logger;

        $this->taskRepository = $entityManager->getRepository(Task::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:cancelcollection')
            ->setDescription('Cancel a collection of tasks')
            ->addArgument(
                'ids',
                InputArgument::REQUIRED,
                'comma-separated list of ids of tasks to cancel'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $this->logger->info('TaskCancelCollectionCommand::execute: raw ids ['.$input->getArgument('ids').']');

        $taskIds = array_filter(explode(',', $input->getArgument('ids')));

        $taskIdsByWorker = array();
        foreach ($taskIds as $taskId) {
            /* @var Task $task */
            $task = $this->taskRepository->find($taskId);

            $taskWorker = $task->getWorker();

            if (empty($taskWorker)) {
                $this->taskService->cancel($task);
            } else {
                if (!isset($taskIdsByWorker[$taskWorker->getHostname()])) {
                    $taskIdsByWorker[$taskWorker->getHostname()] = array();
                }

                $taskIdsByWorker[$taskWorker->getHostname()][] = $task;
            }
        }

        foreach ($taskIdsByWorker as $tasks) {
            $this->workerTaskCancellationService->cancelCollection($tasks);
        }

        return self::RETURN_CODE_OK;
    }
}
