<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Entity\Task\Type\Type as TaskType;
use App\Entity\Task\Output as TaskOutput;
use App\Entity\TimePeriod;
use App\Entity\Worker;
use App\Entity\State;
use App\Repository\TaskOutputRepository;
use App\Repository\TaskRepository;
use App\Services\Resque\QueueService as ResqueQueueService;
use webignition\Url\Encoder as UrlEncoder;
use webignition\Url\Url;

class TaskService
{
    /**
     * All the states a task could be in
     *
     * @var string[]
     */
    private $availableStateNames = [
        Task::STATE_CANCELLED,
        Task::STATE_QUEUED,
        Task::STATE_IN_PROGRESS,
        Task::STATE_COMPLETED,
        Task::STATE_AWAITING_CANCELLATION,
        Task::STATE_QUEUED_FOR_ASSIGNMENT,
        Task::STATE_FAILED_NO_RETRY_AVAILABLE,
        Task::STATE_FAILED_RETRY_AVAILABLE,
        Task::STATE_FAILED_RETRY_LIMIT_REACHED,
        Task::STATE_SKIPPED
    ];

    /**
     * @var string[]
     */
    private $incompleteStateNames = [
        Task::STATE_IN_PROGRESS,
        Task::STATE_QUEUED,
        Task::STATE_QUEUED_FOR_ASSIGNMENT,
    ];

    /**
     * @var string[]
     */
    private $finishedStateNames = [
        Task::STATE_CANCELLED,
        Task::STATE_COMPLETED,
        Task::STATE_FAILED_RETRY_AVAILABLE,
        Task::STATE_FAILED_NO_RETRY_AVAILABLE,
        Task::STATE_FAILED_RETRY_LIMIT_REACHED,
        Task::STATE_SKIPPED,
    ];

    /**
     * @var string[]
     */
    private $cancellableStateNames = [
        Task::STATE_AWAITING_CANCELLATION,
        Task::STATE_IN_PROGRESS,
        Task::STATE_QUEUED,
        Task::STATE_QUEUED_FOR_ASSIGNMENT,
    ];

    private $entityManager;
    private $stateService;
    private $resqueQueueService;
    private $taskRepository;

    /**
     * @var TaskOutputRepository
     */
    private $taskOutputRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        StateService $stateService,
        ResqueQueueService $resqueQueueService,
        TaskRepository $taskRepository
    ) {
        $this->entityManager = $entityManager;
        $this->stateService = $stateService;
        $this->resqueQueueService = $resqueQueueService;

        $this->taskRepository = $taskRepository;
        $this->taskOutputRepository = $entityManager->getRepository(TaskOutput::class);
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

        $cancelledState = $this->stateService->get(Task::STATE_CANCELLED);

        $task->setState($cancelledState);
        $task->clearWorker();

        $timePeriod = $task->getTimePeriod();

        if (empty($timePeriod)) {
            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime());

            $task->setTimePeriod($timePeriod);
        }

        $timePeriod->setEndDateTime(new \DateTime());

        $this->entityManager->persist($task);
    }

    /**
     * @param Task $task
     */
    public function setAwaitingCancellation(Task $task)
    {
        $disAllowedStateNames = [
            Task::STATE_AWAITING_CANCELLATION,
            Task::STATE_CANCELLED,
            Task::STATE_COMPLETED,
        ];

        if (in_array($task->getState()->getName(), $disAllowedStateNames)) {
            return;
        }

        $awaitingCancellationState = $this->stateService->get(Task::STATE_AWAITING_CANCELLATION);

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
     * @param string[] $stateNames
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
     * @param Worker $worker
     * @param int $remoteId
     */
    public function setStarted(Task $task, Worker $worker, $remoteId)
    {
        $inProgressState = $this->stateService->get(Task::STATE_IN_PROGRESS);

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

        $existingOutput = $this->taskOutputRepository->findOneBy([
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

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    /**
     * @return array
     */
    public function getAvailableStateNames()
    {
        return $this->availableStateNames;
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

        $tasks = $this->taskRepository->getCollectionByUrlSetAndTaskTypeAndStates(
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
