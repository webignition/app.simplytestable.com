<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason as JobRejectionReason;
use SimplyTestable\ApiBundle\Repository\TaskRepository;

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
    ];

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param StateService $stateService
     * @param TaskService $taskService
     * @param TaskTypeService $taskTypeService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        StateService $stateService,
        TaskService $taskService,
        TaskTypeService $taskTypeService
    ) {
        $this->entityManager = $entityManager;
        $this->stateService = $stateService;
        $this->taskService = $taskService;
        $this->taskTypeService = $taskTypeService;

        $this->jobRepository = $entityManager->getRepository(Job::class);
        $this->taskRepository = $entityManager->getRepository(Task::class);
    }

    /**
     * @param JobConfiguration $jobConfiguration
     *
     * @return Job
     */
    public function create(JobConfiguration $jobConfiguration)
    {
        $job = new Job();
        $job->setUser($jobConfiguration->getUser());
        $job->setWebsite($jobConfiguration->getWebsite());
        $job->setType($jobConfiguration->getType());

        foreach ($jobConfiguration->getTaskConfigurationsAsCollection()->getEnabled() as $taskConfiguration) {
            $job->addRequestedTaskType($taskConfiguration->getType());

            if ($taskConfiguration->getOptionCount()) {
                $taskTypeOptions = new TaskTypeOptions();
                $taskTypeOptions->setJob($job);
                $taskTypeOptions->setTaskType($taskConfiguration->getType());
                $taskTypeOptions->setOptions($taskConfiguration->getOptions());

                $this->entityManager->persist($taskTypeOptions);
                $job->getTaskTypeOptions()->add($taskTypeOptions);
            }
        }

        $jobConfigurationParameters = $jobConfiguration->getParameters();

        if (!empty($jobConfigurationParameters)) {
            $job->setParameters($jobConfigurationParameters);
        }

        $startingState = $this->stateService->get(Job::STATE_STARTING);

        $job->setState($startingState);
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
        if ($this->isFinished($job) && Job::STATE_FAILED_NO_SITEMAP !== $job->getState()->getName()) {
            return;
        }

        $tasks = $job->getTasks();

        /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
        foreach ($tasks as $task) {
            if ($task->getState()->getName() === Task::STATE_IN_PROGRESS) {
                $this->taskService->setAwaitingCancellation($task);
            } else {
                $this->taskService->cancel($task);
            }
        }

        if ($job->getTimePeriod() instanceof TimePeriod) {
            $job->getTimePeriod()->setEndDateTime(new \DateTime());
        } else {
            $job->setTimePeriod(new TimePeriod());
            $job->getTimePeriod()->setStartDateTime(new \DateTime());
            $job->getTimePeriod()->setEndDateTime(new \DateTime());
        }

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
        $jobStateName = $job->getState()->getName();

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
            $job->getState()->getName(),
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
}
