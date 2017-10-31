<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use webignition\Url\Encoder as UrlEncoder;
use webignition\Url\Url;

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
    private $cancellableStateNames = [
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
     * @return string[]
     */
    public function getFinishedStateNames()
    {
        return $this->finishedStateNames;
    }

    /**
     * @return string[]
     */
    public function getCancellableStateNames()
    {
        return $this->cancellableStateNames;
    }

    /**
     * @param Task $task
     */
    public function cancel(Task $task)
    {
        if ($this->isFinished($task)) {
            return;
        }

        $cancelledState = $this->stateService->fetch(self::CANCELLED_STATE);

        $task->setState($cancelledState);
        $task->clearWorker();

        $timePeriod = $task->getTimePeriod();

        if (empty($timePeriod)) {
            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime());

            $task->setTimePeriod($timePeriod);
        }

        $timePeriod->setEndDateTime(new \DateTime());

        $this->getManager()->persist($task);
    }

    /**
     * @param Task $task
     */
    public function setAwaitingCancellation(Task $task)
    {
        $disAllowedStateNames = [
            self::AWAITING_CANCELLATION_STATE,
            self::CANCELLED_STATE,
            self::COMPLETED_STATE,
        ];

        if (in_array($task->getState()->getName(), $disAllowedStateNames)) {
            return;
        }

        $awaitingCancellationState = $this->stateService->fetch(self::AWAITING_CANCELLATION_STATE);

        $task->setState($awaitingCancellationState);
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
        return $this->isTaskInStates($task, $this->cancellableStateNames);
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
     */
    public function persist(Task $task)
    {
        $this->getManager()->persist($task);
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
     * @param \DateTime $endDateTime
     * @param TaskOutput $output
     * @param State $state
     */
    public function complete(Task $task, \DateTime $endDateTime, TaskOutput $output, State $state)
    {
        $taskIsInCorrectState = false;

        foreach ($this->getIncompleteStateNames() as $incompleteStateName) {
            if ($task->getState()->getName() === $incompleteStateName) {
                $taskIsInCorrectState = true;
            }
        }

        if (!$taskIsInCorrectState) {
            return;
        }

        $output->generateHash();

        $taskOutputRepository = $this->entityManager->getRepository(Output::class);

        $existingOutput = $taskOutputRepository->findOneBy([
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

        $this->persistAndFlush($task);
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
     * @param string $parameterHash
     * @param State[] $states
     *
     * @return Task[]
     */
    public function getEquivalentTasks($url, TaskType $taskType, $parameterHash, $states)
    {
        $urlEncoder = new UrlEncoder();

        $urlSet = array_unique([
            $url,
            urldecode($url),
            (string)$urlEncoder->encode(new Url(($url)))
        ]);

        $tasks = $this->getEntityRepository()->getCollectionByUrlSetAndTaskTypeAndStates(
            $urlSet,
            $taskType,
            $states
        );

        $parameterHash = trim($parameterHash);

        if (!empty($parameterHash)) {
            foreach ($tasks as $taskIndex => $task) {
                if ($task->getParametersHash() !== $parameterHash) {
                    unset($tasks[$taskIndex]);
                }
            }
        }

        return $tasks;
    }
}
