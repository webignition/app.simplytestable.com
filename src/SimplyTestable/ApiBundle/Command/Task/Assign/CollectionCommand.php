<?php
namespace SimplyTestable\ApiBundle\Command\Task\Assign;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskPreProcessor\Factory as TaskPreProcessorFactory;
use SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectionCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_FAILED_NO_WORKERS = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TaskPreProcessorFactory
     */
    private $taskPreprocessorFactory;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var ResqueJobFactory
     */
    private $resqueJobFactory;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var WorkerTaskAssignmentService
     */
    private $workerTaskAssignmentService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var EntityRepository
     */
    private $workerRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManager $entityManager
     * @param TaskPreProcessorFactory $taskPreProcessorFactory
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param StateService $stateService
     * @param WorkerTaskAssignmentService $workerTaskAssignmentService
     * @param LoggerInterface $logger
     * @param TaskRepository $taskRepository
     * @param EntityRepository $workerRepository
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManager $entityManager,
        TaskPreProcessorFactory $taskPreProcessorFactory,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        StateService $stateService,
        WorkerTaskAssignmentService $workerTaskAssignmentService,
        LoggerInterface $logger,
        TaskRepository $taskRepository,
        EntityRepository $workerRepository,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->taskPreprocessorFactory = $taskPreProcessorFactory;
        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;
        $this->stateService = $stateService;
        $this->workerTaskAssignmentService = $workerTaskAssignmentService;
        $this->logger = $logger;
        $this->taskRepository = $taskRepository;
        $this->workerRepository = $workerRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:assigncollection')
            ->setDescription('Assign a collection of tasks to workers')
            ->addArgument('ids', InputArgument::REQUIRED, 'ids of tasks to assign')
            ->addArgument('worker', InputArgument::OPTIONAL, 'hostname of worker to which to assign tasks')
            ->setHelp('Assign a collection of tasks to workers');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $taskIds = array_filter(explode(',', $input->getArgument('ids')));

        if (empty($taskIds)) {
            return self::RETURN_CODE_OK;
        }

        $tasks = $this->taskRepository->findBy([
            'id' => $taskIds,
        ]);

        foreach ($tasks as $taskIndex => $task) {
            $taskPreprocessor = $this->taskPreprocessorFactory->getPreprocessor($task->getType());

            if (!empty($taskPreprocessor)) {
                $preProcessorResponse = false;

                try {
                    $preProcessorResponse = $taskPreprocessor->process($task);
                } catch (\Exception $e) {
                }

                if ($preProcessorResponse === true) {
                    unset($tasks[$taskIndex]);
                }
            }
        }

        if (count($tasks) === 0) {
            return self::RETURN_CODE_OK;
        }

        $activeWorkers = $this->workerRepository->findBy([
            'state' => $this->stateService->get(Worker::STATE_ACTIVE),
        ]);

        $workers = [];

        if (is_null($input->getArgument('worker'))) {
            $workers = $activeWorkers;
        } else {
            $selectedWorker = trim($input->getArgument('worker'));

            foreach ($activeWorkers as $activeWorker) {
                if ($activeWorker->getHostname() == $selectedWorker) {
                    $workers[] = $activeWorker;
                }
            }
        }

        if (count($workers) === 0) {
            $this->logger->error("TaskAssignCollectionCommand::execute: Cannot assign, no workers.");
            $this->requeueAssignment($taskIds);

            return self::RETURN_CODE_FAILED_NO_WORKERS;
        }

        $jobInProgressState = $this->stateService->get(JobService::IN_PROGRESS_STATE);

        $response = $this->workerTaskAssignmentService->assignCollection($tasks, $workers);
        if ($response === 0) {
            /* @var Job $job */
            $job = $tasks[0]->getJob();
            if ($job->getState()->getName() == 'job-queued') {
                $job->setState($jobInProgressState);
                $this->entityManager->persist($job);
            }

            $this->entityManager->flush();
        } else {
            $this->requeueAssignment($taskIds);
        }

        return $response;
    }

    /**
     * @param int[] $taskIds
     */
    private function requeueAssignment($taskIds)
    {
        $this->resqueQueueService->enqueue(
            $this->resqueJobFactory->create(
                'task-assign-collection',
                ['ids' => implode(',', $taskIds)]
            )
        );
    }
}
