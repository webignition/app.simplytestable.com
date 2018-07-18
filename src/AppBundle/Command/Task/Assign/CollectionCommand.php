<?php

namespace AppBundle\Command\Task\Assign;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use AppBundle\Entity\Job\Job;
use AppBundle\Entity\Task\Task;
use AppBundle\Entity\Worker;
use AppBundle\Resque\Job\Task\AssignCollectionJob;
use AppBundle\Services\ApplicationStateService;
use AppBundle\Services\Resque\QueueService as ResqueQueueService;
use AppBundle\Services\StateService;
use AppBundle\Services\TaskPreProcessor\Factory as TaskPreProcessorFactory;
use AppBundle\Services\WorkerTaskAssignmentService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectionCommand extends Command
{
    const NAME = 'simplytestable:task:assigncollection';

    const RETURN_CODE_OK = 0;
    const RETURN_CODE_FAILED_NO_WORKERS = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManagerInterface
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
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param TaskPreProcessorFactory $taskPreProcessorFactory
     * @param ResqueQueueService $resqueQueueService
     * @param StateService $stateService
     * @param WorkerTaskAssignmentService $workerTaskAssignmentService
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        TaskPreProcessorFactory $taskPreProcessorFactory,
        ResqueQueueService $resqueQueueService,
        StateService $stateService,
        WorkerTaskAssignmentService $workerTaskAssignmentService,
        LoggerInterface $logger,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->taskPreprocessorFactory = $taskPreProcessorFactory;
        $this->resqueQueueService = $resqueQueueService;
        $this->stateService = $stateService;
        $this->workerTaskAssignmentService = $workerTaskAssignmentService;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
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
        if ($this->applicationStateService->isInReadOnlyMode()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $taskIds = $this->createTaskIdCollection($input->getArgument('ids'));

        if (empty($taskIds)) {
            return self::RETURN_CODE_OK;
        }

        $taskRepository = $this->entityManager->getRepository(Task::class);
        $workerRepository = $this->entityManager->getRepository(Worker::class);

        /* @var Task[] $tasks */
        $tasks = $taskRepository->findBy([
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

        $activeWorkers = $workerRepository->findBy([
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

        $jobInProgressState = $this->stateService->get(Job::STATE_IN_PROGRESS);

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
     * @param string $taskIdsString
     *
     * @return int[]
     */
    private function createTaskIdCollection($taskIdsString)
    {
        if (substr_count($taskIdsString, ',')) {
            return array_filter(explode(',', $taskIdsString));
        }

        if (substr_count($taskIdsString, ':')) {
            $rangeLimits = explode(':', $taskIdsString);

            $start = (int)$rangeLimits[0];
            $end = (int)$rangeLimits[1];

            $idCollection = [];

            for ($id = $start; $id <= $end; $id++) {
                $idCollection[] = $id;
            }

            return $idCollection;
        }

        if (ctype_digit($taskIdsString) || is_int($taskIdsString)) {
            return [
                (int)$taskIdsString,
            ];
        }

        return [];
    }

    /**
     * @param int[] $taskIds
     */
    private function requeueAssignment($taskIds)
    {
        $this->resqueQueueService->enqueue(new AssignCollectionJob(['ids' => implode(',', $taskIds)]));
    }
}
