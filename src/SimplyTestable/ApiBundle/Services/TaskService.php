<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Repository\TaskOutputRepository;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;

class TaskService extends EntityService
{
    const CANCELLED_STATE = 'task-cancelled';
    const QUEUED_STATE = 'task-queued';
    const IN_PROGRESS_STATE = 'task-in-progress';
    const COMPLETED_STATE = 'task-completed';
    const AWAITING_CANCELLATION_STATE = 'task-awaiting-cancellation';
    const QUEUED_FOR_ASSIGNMENT_STATE = 'task-queued-for-assignment';
    const TASK_FAILED_NO_RETRY_AVAILABLE_STATE = 'task-failed-no-retry-available';
    const TASK_FAILED_RETRY_AVAILABLE_STATE = 'task-failed-retry-available';
    const TASK_FAILED_RETRY_LIMIT_REACHED_STATE = 'task-failed-retry-limit-reached';
    const TASK_SKIPPED_STATE = 'task-skipped';

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var TaskOutputRepository
     */
    private $taskOutputRepository;

    /**
     * All the states a task could be in
     *
     * @var string[]
     */
    private $availableStateNames = [
        self::CANCELLED_STATE,
        self::QUEUED_STATE,
        self::IN_PROGRESS_STATE,
        self::COMPLETED_STATE,
        self::AWAITING_CANCELLATION_STATE,
        self::QUEUED_FOR_ASSIGNMENT_STATE,
        self::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
        self::TASK_FAILED_RETRY_AVAILABLE_STATE,
        self::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
        self::TASK_SKIPPED_STATE
    ];

    /**
     * @var string[]
     */
    private $incompleteStateNames = [
        self::IN_PROGRESS_STATE,
        self::QUEUED_STATE,
        self::QUEUED_FOR_ASSIGNMENT_STATE,
    ];

    /**
     * @var string[]
     */
    private $finishedStateNames = [
        self::CANCELLED_STATE,
        self::COMPLETED_STATE,
        self::TASK_FAILED_RETRY_AVAILABLE_STATE,
        self::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
        self::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
        self::TASK_SKIPPED_STATE,
    ];

    /**
     * @var string[]
     */
    private $cancellableStates = [
        self::AWAITING_CANCELLATION_STATE,
        self::IN_PROGRESS_STATE,
        self::QUEUED_STATE,
        self::QUEUED_FOR_ASSIGNMENT_STATE,
    ];

    /**
     * @param EntityManager $entityManager
     * @param StateService $stateService
     * @param ResqueQueueService $resqueQueueService
     */
    public function __construct(
        EntityManager $entityManager,
        StateService $stateService,
        ResqueQueueService $resqueQueueService
    ) {
        parent::__construct($entityManager);

        $this->stateService = $stateService;
        $this->resqueQueueService = $resqueQueueService;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityName()
    {
        return Task::class;
    }

    /**
     * @param Task $task
     *
     * @return Task
     */
    public function cancel(Task $task)
    {
        if ($this->isFinished($task)) {
            return $task;
        }

        $cancelledState = $this->stateService->fetch(self::CANCELLED_STATE);

        $task->setState($cancelledState);
        $task->clearWorker();

        if ($task->getTimePeriod() instanceof TimePeriod) {
            $task->getTimePeriod()->setEndDateTime(new \DateTime());
        } else {
            $task->setTimePeriod(new TimePeriod());
            $task->getTimePeriod()->setStartDateTime(new \DateTime());
            $task->getTimePeriod()->setEndDateTime(new \DateTime());
        }

        $this->getManager()->persist($task);

        return $task;
    }

    /**
     * @param Task $task
     *
     * @return Task
     */
    public function setAwaitingCancellation(Task $task)
    {
        $disAllowedStateNames = [
            self::AWAITING_CANCELLATION_STATE,
            self::CANCELLED_STATE,
            self::COMPLETED_STATE,
        ];

        if (in_array($task->getState()->getName(), $disAllowedStateNames)) {
            return $task;
        }

        $awaitingCancellationState = $this->stateService->fetch(self::AWAITING_CANCELLATION_STATE);

        $task->setState($awaitingCancellationState);

        return $task;
    }

    /**
     * @param int $id
     *
     * @return Task
     */
    public function getById($id)
    {
        /* @var Task $task */
        $task = $this->getEntityRepository()->find($id);

        return $task;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function isCompleted(Task $task)
    {
        return $task->getState()->getName() === self::COMPLETED_STATE;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function isQueued(Task $task)
    {
        return $task->getState()->getName() === self::QUEUED_STATE;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function isQueuedForAssignment(Task $task)
    {
        return $task->getState()->getName() === self::QUEUED_FOR_ASSIGNMENT_STATE;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function isFailedRetryAvailable(Task $task)
    {
        return $task->getState()->getName() === self::TASK_FAILED_RETRY_AVAILABLE_STATE;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function isFailedNoRetryAvailable(Task $task)
    {
        return $task->getState()->getName() === self::TASK_FAILED_NO_RETRY_AVAILABLE_STATE;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function isFailedRetryLimitReached(Task $task)
    {
        return $task->getState()->getName() === self::TASK_FAILED_RETRY_LIMIT_REACHED_STATE;
    }

    /**
     *
     * @param Task $task
     * @return bool
     */
    public function isInProgress(Task $task)
    {
        return $task->getState()->getName() === self::IN_PROGRESS_STATE;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function isSkipped(Task $task)
    {
        return $task->getState()->getName() === self::TASK_SKIPPED_STATE;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function isFinished(Task $task)
    {
        return $this->isTaskInStates($task, $this->finishedStateNames);
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function isCancellable(Task $task)
    {
        return $this->isTaskInStates($task, $this->cancellableStates);
    }

    /**
     * @param Task $task
     * @param string $stateNames
     *
     * @return bool
     */
    private function isTaskInStates(Task $task, $stateNames)
    {
        return in_array($task->getState()->getName(), $stateNames);
    }

    /**
     * @return string[]
     */
    public function getIncompleteStateNames()
    {
        return $this->incompleteStateNames;
    }

    /**
     * @param Task $task
     *
     * @return Task
     */
    public function persist(Task $task)
    {
        $this->getManager()->persist($task);
        return $task;
    }

    /**
     * @param Task $task
     *
     * @return Task
     */
    public function persistAndFlush(Task $task)
    {
        $this->getManager()->persist($task);
        $this->getManager()->flush();
        return $task;
    }

    /**
     * @param Task $task
     * @param Worker $worker
     * @param int $remoteId
     */
    public function setStarted(Task $task, Worker $worker, $remoteId)
    {
        $inProgressState = $this->stateService->fetch(self::IN_PROGRESS_STATE);

        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());

        $task->setRemoteId($remoteId);
        $task->setState($inProgressState);
        $task->setTimePeriod($timePeriod);
        $task->setWorker($worker);
    }

    /**
     * @param Task $task
     *
     * @return Task
     */
    public function reQueue(Task $task)
    {
        $queuedState = $this->stateService->fetch(self::QUEUED_STATE);

        $task->setState($queuedState);
        $this->getManager()->persist($task);
        return $task;
    }

    /**
     * @param int $remoteId
     *
     * @return Task
     */
    public function getByRemoteId($remoteId)
    {
        /* @var Task $task */
        $task = $this->getEntityRepository()->findBy([
            'remoteId' => $remoteId
        ]);

        return $task;
    }

    /**
     * @param Task $task
     * @param \DateTime $endDateTime
     * @param TaskOutput $output
     * @param State $state
     *
     * @return Task
     */
    public function complete(Task $task, \DateTime $endDateTime, TaskOutput $output, State $state, $flush = true)
    {
        $taskIsInCorrectState = false;

        $incompleteStates = $this->stateService->fetchCollection($this->getIncompleteStateNames());

        foreach ($incompleteStates as $incompleteState) {
            if ($task->getState()->equals($incompleteState)) {
                $taskIsInCorrectState = true;
            }
        }

        if (!$taskIsInCorrectState) {
            return $task;
        }

        $output->generateHash();

        $existingOutput = $this->getTaskOutputEntityRepository()->findOneBy([
            'hash' => $output->getHash(),
        ]);

        if (!is_null($existingOutput)) {
            $output = $existingOutput;
        }

        if (is_null($task->getTimePeriod())) {
            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime($endDateTime);
            $task->setTimePeriod($timePeriod);
        }

        $task->getTimePeriod()->setEndDateTime($endDateTime);
        $task->setOutput($output);
        $task->setState($state);
        $task->clearWorker();
        $task->clearRemoteId();

        return $this->persistAndFlush($task);
    }

    /**
     * @return TaskOutputRepository
     */
    private function getTaskOutputEntityRepository()
    {
        if (is_null($this->taskOutputRepository)) {
            $this->taskOutputRepository = $this->entityManager->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output');
        }

        return $this->taskOutputRepository;
    }

    /**
     * @param Worker $worker
     * @param int $remoteId
     *
     * @return Task
     */
    public function getByWorkerAndRemoteId(Worker $worker, $remoteId)
    {
        $tasks = $this->getEntityRepository()->findBy([
            'worker' => $worker,
            'remoteId' => $remoteId
        ], [
            'id' => 'DESC'
        ], 1);

        if (count($tasks) === 0) {
            return null;
        }

        return $tasks[0];
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getUrlCountByJob(Job $job)
    {
        return $this->getEntityRepository()->findUrlCountByJob($job);
    }

    /**
     * @param Job $job
     *
     * @return array
     */
    public function getUrlsByJob(Job $job)
    {
        return $this->getEntityRepository()->findUrlsByJob($job);
    }

    /**
     * @param Job $job
     *
     * @return Task[]
     */
    public function getAwaitingCancellationByJob(Job $job)
    {
        $awaitingCancellationState = $this->stateService->fetch(self::AWAITING_CANCELLATION_STATE);

        return $this->getEntityRepository()->findBy([
            'job' => $job,
            'state' => $awaitingCancellationState,
        ]);
    }

    /**
     * @return Job[]
     */
    public function getJobsWithQueuedTasks()
    {
        return $this->getEntityRepository()->findJobsbyTaskState(
            $this->stateService->fetch(self::QUEUED_STATE)
        );
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getCountByJob(Job $job)
    {
        return $this->getEntityRepository()->getCountByJob($job);
    }

    /**
     * @return array
     */
    public function getAvailableStateNames()
    {
        return $this->availableStateNames;
    }

    /**
     * @return TaskRepository
     */
    public function getEntityRepository()
    {
        return parent::getEntityRepository();
    }

    /**
     * @param string $url
     * @param TaskType $taskType
     * @param string $parameter_hash
     * @param State[] $states
     *
     * @return Task[]
     */
    public function getEquivalentTasks($url, TaskType $taskType, $parameter_hash, $states)
    {
        $urlEncoder = new \webignition\Url\Encoder();

        $urlSet = array_unique([
            $url,
            urldecode($url),
            (string)$urlEncoder->encode(new \webignition\Url\Url(($url)))
        ]);

        $tasks = $this->getEntityRepository()->getCollectionByUrlSetAndTaskTypeAndStates(
            $urlSet,
            $taskType,
            $states
        );

        $parameter_hash = trim($parameter_hash);

        if ($parameter_hash !== '') {
            foreach ($tasks as $taskIndex => $task) {
                if ($task->getParametersHash() !== $parameter_hash) {
                    unset($tasks[$taskIndex]);
                }
            }
        }

        return $tasks;
    }
}
