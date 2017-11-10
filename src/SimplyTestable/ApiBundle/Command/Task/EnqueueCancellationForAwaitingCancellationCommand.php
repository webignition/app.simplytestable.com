<?php
namespace SimplyTestable\ApiBundle\Command\Task;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnqueueCancellationForAwaitingCancellationCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var ResqueJobFactory
     */
    private $resqueJobFactory;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param StateService $stateService
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        StateService $stateService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->stateService = $stateService;
        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;

        $this->taskRepository = $entityManager->getRepository(Task::class);
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
        if ($this->applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $taskAwaitingCancellationState = $this->stateService->get(TaskService::AWAITING_CANCELLATION_STATE);

        $taskIds = $this->taskRepository->getIdsByState(
            $taskAwaitingCancellationState
        );

        $output->writeln(count($taskIds).' tasks to enqueue for cancellation');

        if (empty($taskIds)) {
            return self::RETURN_CODE_OK;
        }

        $output->writeln('Enqueuing for cancellation tasks '.  implode(',', $taskIds));

        $this->resqueQueueService->enqueue(
            $this->resqueJobFactory->create(
                'task-cancel-collection',
                ['ids' => implode(',', $taskIds)]
            )
        );

        return self::RETURN_CODE_OK;
    }
}
