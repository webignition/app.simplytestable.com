<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Account\Plan\Constraint;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Entity\Job\TaskTypeOptions;
use App\Entity\Job\Ammendment;
use App\Entity\Job\Configuration as JobConfiguration;
use App\Repository\JobRepository;
use App\Entity\Account\Plan\Constraint as AccountPlanConstraint;
use App\Entity\Job\RejectionReason as JobRejectionReason;
use App\Repository\TaskRepository;

class JobService
{
    /**
     * @var string[]
     */
    private $incompleteStateNames = [
        Job::STATE_STARTING,
        Job::STATE_RESOLVING,
        Job::STATE_RESOLVED,
        Job::STATE_IN_PROGRESS,
        Job::STATE_PREPARING,
        Job::STATE_QUEUED
    ];

    /**
     * @var string[]
     */
    private $finishedStates = [
        Job::STATE_REJECTED,
        Job::STATE_CANCELLED,
        Job::STATE_COMPLETED,
        Job::STATE_FAILED_NO_SITEMAP,
        Job::STATE_EXPIRED,
    ];

    private $stateService;
    private $taskService;
    private $taskTypeService;
    private $entityManager;
    private $jobRepository;
    private $taskRepository;
    private $jobIdentifierFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        StateService $stateService,
        TaskService $taskService,
        TaskTypeService $taskTypeService,
        JobRepository $jobRepository,
        TaskRepository $taskRepository,
        JobIdentifierFactory $jobIdentifierFactory
    ) {
        $this->entityManager = $entityManager;
        $this->stateService = $stateService;
        $this->taskService = $taskService;
        $this->taskTypeService = $taskTypeService;
        $this->jobRepository = $jobRepository;
        $this->taskRepository = $taskRepository;
        $this->jobIdentifierFactory = $jobIdentifierFactory;
    }

    /**
     * @param JobConfiguration $jobConfiguration
     *
     * @return Job
     */
    public function create(JobConfiguration $jobConfiguration)
    {
        $job = Job::create(
            $jobConfiguration->getUser(),
            $jobConfiguration->getWebsite(),
            $jobConfiguration->getType(),
            $this->stateService->get(Job::STATE_STARTING),
            $jobConfiguration->getParameters()
        );

        foreach ($jobConfiguration->getTaskConfigurationCollection() as $taskConfiguration) {
            if ($taskConfiguration->getIsEnabled()) {
                $job->addRequestedTaskType($taskConfiguration->getType());
            }

            if ($taskConfiguration->getOptionCount()) {
                $taskTypeOptions = new TaskTypeOptions();
                $taskTypeOptions->setJob($job);
                $taskTypeOptions->setTaskType($taskConfiguration->getType());
                $taskTypeOptions->setOptions($taskConfiguration->getOptions());

                $this->entityManager->persist($taskTypeOptions);
                $job->getTaskTypeOptions()->add($taskTypeOptions);
            }
        }

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $job->setIdentifier($this->jobIdentifierFactory->create($job));

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        return $job;
    }

    /**
     * @param Job $job
     * @param string $reason
     *
     * @param Constraint $constraint
     */
    public function addAmmendment(Job $job, $reason, Constraint $constraint = null)
    {
        $ammendment = new Ammendment();
        $ammendment->setJob($job);
        $ammendment->setReason($reason);

        if (!is_null($constraint)) {
            $ammendment->setConstraint($constraint);
        }

        $this->entityManager->persist($ammendment);
        $this->entityManager->flush();
    }

    /**
     * @param Job $job
     */
    public function cancel(Job $job)
    {
        if ($this->isFinished($job) && Job::STATE_FAILED_NO_SITEMAP !== (string) $job->getState()) {
            return;
        }

        $tasks = $job->getTasks();

        /* @var Task $task */
        foreach ($tasks as $task) {
            $this->taskService->cancel($task);
        }

        if (empty($job->getTimePeriod())) {
            $job->setStartDateTime(new \DateTime());
        }

        $job->setEndDateTime(new \DateTime());

        $cancelledState = $this->stateService->get(Job::STATE_CANCELLED);

        $job->setState($cancelledState);

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    /**
     * @param Job $job
     */
    public function cancelIncompleteTasks(Job $job)
    {
        foreach ($job->getTasks() as $task) {
            if ($task->getState()->getName() !== Task::STATE_COMPLETED) {
                $this->taskService->cancel($task);
            }
        }
    }

    /**
     * @param Job $job
     * @param string $reason
     * @param AccountPlanConstraint|null $constraint
     */
    public function reject(Job $job, $reason, AccountPlanConstraint $constraint = null)
    {
        $jobStateName = (string) $job->getState();

        $allowedStateNames = [
            Job::STATE_STARTING,
            Job::STATE_PREPARING,
            Job::STATE_RESOLVING,
        ];

        if (!in_array($jobStateName, $allowedStateNames)) {
            return;
        }

        $rejectedState = $this->stateService->get(Job::STATE_REJECTED);
        $job->setState($rejectedState);

        $now = new \DateTime();
        $job->setStartDateTime($now);
        $job->setEndDateTime($now);

        $rejectionReason = new JobRejectionReason();
        $rejectionReason->setConstraint($constraint);
        $rejectionReason->setJob($job);
        $rejectionReason->setReason($reason);

        $this->entityManager->persist($rejectionReason);

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isFinished(Job $job)
    {
        return in_array(
            (string) $job->getState(),
            $this->finishedStates
        );
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function hasIncompleteTasks(Job $job)
    {
        $incompleteTaskCount = $this->taskRepository->getCountByJobAndStates(
            $job,
            $this->stateService->getCollection($this->taskService->getIncompleteStateNames())
        );

        return $incompleteTaskCount > 0;
    }

    /**
     * @param Job $job
     */
    public function complete(Job $job)
    {
        if ($this->isFinished($job)) {
            return;
        }

        if ($this->hasIncompleteTasks($job)) {
            return;
        }

        $completedState = $this->stateService->get(Job::STATE_COMPLETED);

        $job->getTimePeriod()->setEndDateTime(new \DateTime());
        $job->setState($completedState);

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    /**
     * @return Job[]
     */
    public function getUnfinishedJobsWithTasksAndNoIncompleteTasks()
    {
        $incompleteStates = $this->stateService->getCollection($this->getIncompleteStateNames());

        /* @var Job[] $jobs */
        $jobs = $this->jobRepository->findBy([
            'state' => $incompleteStates,
        ]);

        foreach ($jobs as $jobIndex => $job) {
            // Exclude jobs with no tasks
            if (count($job->getTasks()) === 0) {
                unset($jobs[$jobIndex]);
            }

            // Exclude jobs with incomplete tasks
            if ($this->hasIncompleteTasks($job)) {
                unset($jobs[$jobIndex]);
            }
        }

        return $jobs;
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getCountOfTasksWithErrors(Job $job)
    {
        return $this->getCountOfTasksWithIssues($job, TaskRepository::ISSUE_TYPE_ERROR);
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getCountOfTasksWithWarnings(Job $job)
    {
        return $this->getCountOfTasksWithIssues($job, TaskRepository::ISSUE_TYPE_WARNING);
    }

    /**
     * @param Job $job
     * @param string $issueType
     *
     * @return int
     */
    private function getCountOfTasksWithIssues(Job $job, $issueType)
    {
        return $this->taskRepository->getCountWithIssuesByJob(
            $job,
            $issueType,
            $this->stateService->getCollection([
                Task::STATE_CANCELLED,
                Task::STATE_AWAITING_CANCELLATION,
            ])
        );
    }


    /**
     * @param Job $job
     *
     * @return int
     */
    public function getCancelledTaskCount(Job $job)
    {
        return $this->taskRepository->getCountByJobAndStates(
            $job,
            $this->stateService->getCollection([
                Task::STATE_CANCELLED,
                Task::STATE_AWAITING_CANCELLATION,
            ])
        );
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getSkippedTaskCount(Job $job)
    {
        return $this->taskRepository->getCountByJobAndStates($job, [
            $this->stateService->get(Task::STATE_SKIPPED),
        ]);
    }

    /**
     * @return string[]
     */
    public function getIncompleteStateNames()
    {
        return $this->incompleteStateNames;
    }

    /**
     * @return string[]
     */
    public function getFinishedStateNames()
    {
        return $this->finishedStates;
    }

    public function expire(Job $job)
    {
        $jobExpiredState = $this->stateService->get(Job::STATE_EXPIRED);

        $job->setState($jobExpiredState);

        foreach ($job->getTasks() as $task) {
            $this->taskService->expire($task);
            $this->entityManager->persist($task);
        }

        $this->entityManager->persist($job);
    }
}
