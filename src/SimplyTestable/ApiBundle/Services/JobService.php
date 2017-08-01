<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Repository\JobRepository;

class JobService extends EntityService
{
    const STARTING_STATE = 'job-new';
    const CANCELLED_STATE = 'job-cancelled';
    const COMPLETED_STATE = 'job-completed';
    const IN_PROGRESS_STATE = 'job-in-progress';
    const PREPARING_STATE = 'job-preparing';
    const QUEUED_STATE = 'job-queued';
    const FAILED_NO_SITEMAP_STATE = 'job-failed-no-sitemap';
    const REJECTED_STATE = 'job-rejected';
    const RESOLVING_STATE = 'job-resolving';
    const RESOLVED_STATE = 'job-resolved';

    /**
     * @var string[]
     */
    private $incompleteStateNames = [
        self::STARTING_STATE,
        self::RESOLVING_STATE,
        self::RESOLVED_STATE,
        self::IN_PROGRESS_STATE,
        self::PREPARING_STATE,
        self::QUEUED_STATE
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
     * @param EntityManager $entityManager
     * @param StateService $stateService
     * @param TaskService $taskService
     * @param TaskTypeService $taskTypeService
     */
    public function __construct(
        EntityManager $entityManager,
        StateService $stateService,
        TaskService $taskService,
        TaskTypeService $taskTypeService
    ) {
        parent::__construct($entityManager);
        $this->stateService = $stateService;
        $this->taskService = $taskService;
        $this->taskTypeService = $taskTypeService;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityName()
    {
        return Job::class;
    }

    /**
     * @param int $id
     *
     * @return Job
     */
    public function getById($id)
    {
        return $this->getEntityRepository()->find($id);
    }

    /**
     * @return State
     */
    public function getCompletedState()
    {
        return $this->stateService->fetch(self::COMPLETED_STATE);
    }

    /**
     * @return State
     */
    public function getInProgressState()
    {
        return $this->stateService->fetch(self::IN_PROGRESS_STATE);
    }

    /**
     * @return State
     */
    public function getCancelledState()
    {
        return $this->stateService->fetch(self::CANCELLED_STATE);
    }

    /**
     * @return State
     */
    public function getStartingState()
    {
        return $this->stateService->fetch(self::STARTING_STATE);
    }

    /**
     * @return State
     */
    public function getPreparingState()
    {
        return $this->stateService->fetch(self::PREPARING_STATE);
    }

    /**
     * @return State
     */
    public function getQueuedState()
    {
        return $this->stateService->fetch(self::QUEUED_STATE);
    }

    /**
     * @return State
     */
    public function getFailedNoSitemapState()
    {
        return $this->stateService->fetch(self::FAILED_NO_SITEMAP_STATE);
    }

    /**
     * @return State
     */
    public function getRejectedState()
    {
        return $this->stateService->fetch(self::REJECTED_STATE);
    }

    /**
     * @return State
     */
    public function getResolvingState()
    {
        return $this->stateService->fetch(self::RESOLVING_STATE);
    }

    /**
     * @return State
     */
    public function getResolvedState()
    {
        return $this->stateService->fetch(self::RESOLVED_STATE);
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isNew(Job $job)
    {
        return $job->getState()->equals($this->getStartingState());
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isPreparing(Job $job)
    {
        return $job->getState()->equals($this->getPreparingState());
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isQueued(Job $job)
    {
        return $job->getState()->equals($this->getQueuedState());
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isRejected(Job $job)
    {
        return $job->getState()->equals($this->getRejectedState());
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isFailedNoSitepmap(Job $job)
    {
        return $job->getState()->equals($this->getFailedNoSitemapState());
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isCompleted(Job $job)
    {
        return $job->getState()->equals($this->getCompletedState());
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isResolved(Job $job)
    {
        return $job->getState()->equals($this->getResolvedState());
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isResolving(Job $job)
    {
        return $job->getState()->equals($this->getResolvingState());
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

                $this->getManager()->persist($taskTypeOptions);
                $job->getTaskTypeOptions()->add($taskTypeOptions);
            }
        }

        if ($jobConfiguration->hasParameters()) {
            $job->setParameters($jobConfiguration->getParameters());
        }

        $job->setState($this->getStartingState());
        $this->getManager()->persist($job);
        $this->getManager()->flush();

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

        $this->getManager()->persist($ammendment);
        $this->getManager()->flush();
    }

    /**
     * @param Job $job
     * @return Job
     */
    public function cancel(Job $job)
    {
        if ($this->isFinished($job) && $job->getState()->equals($this->getFailedNoSitemapState()) === false) {
            return $job;
        }

        $tasks = $job->getTasks();

        /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
        foreach ($tasks as $task) {
            if ($this->taskService->isInProgress($task)) {
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

        $job->setState($this->getCancelledState());
        return $this->persistAndFlush($job);
    }

    /**
     * @param Job $job
     */
    public function cancelIncompleteTasks(Job $job)
    {
        foreach ($job->getTasks() as $task) {
            if (!$this->taskService->isCompleted($task)) {
                $this->taskService->cancel($task);
            }
        }
    }

    /**
     * @param Job $job
     *
     * @return Job
     */
    public function reject(Job $job)
    {
        if (!$this->isNew($job) && !$this->isPreparing($job) && !$this->isResolving($job)) {
            return $job;
        }

        $job->setState($this->getRejectedState());
        return $this->persistAndFlush($job);
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function isFinished(Job $job)
    {
        if ($this->isRejected($job)) {
            return true;
        }

        if ($this->isCancelled($job)) {
            return true;
        }

        if ($this->isCompleted($job)) {
            return true;
        }

        if ($this->isFailedNoSitemap($job)) {
            return true;
        }

        return false;
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isCancelled(Job $job)
    {
        return $job->getState()->equals($this->getCancelledState());
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isFailedNoSitemap(Job $job)
    {
        return $job->getState()->equals($this->getFailedNoSitemapState());
    }

    /**
     * @param Job $job
     *
     * @return Job|bool
     */
    public function fetch(Job $job)
    {
        $jobs = $this->getEntityRepository()->findBy(array(
            'state' => $job->getState(),
            'user' => $job->getUser(),
            'website' => $job->getWebsite()
        ));

        /* @var $comparator Job */
        foreach ($jobs as $comparator) {
            if ($job->equals($comparator)) {
                return $comparator;
            }
        }

        return false;
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function has(Job $job)
    {
        return $this->fetch($job) !== false;
    }

    /**
     * @param Job $job
     *
     * @return Job
     */
    public function persistAndFlush(Job $job)
    {
        $this->getManager()->persist($job);
        $this->getManager()->flush();
        return $job;
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function hasIncompleteTasks(Job $job)
    {
        $incompleteTaskStates = $this->taskService->getIncompleteStates();
        foreach ($incompleteTaskStates as $state) {
            $taskCount = $this->taskService->getCountByJobAndState($job, $state);
            if ($taskCount > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Job $job
     *
     * @return Job
     */
    public function complete(Job $job)
    {
        if ($this->isFinished($job)) {
            return $job;
        }

        if ($this->hasIncompleteTasks($job)) {
            return $job;
        }

        $job->getTimePeriod()->setEndDateTime(new \DateTime());
        $job->setState($this->getCompletedState());

        return $this->persistAndFlush($job);
    }

    /**
     * @return Job[]
     */
    public function getUnfinishedJobsWithTasksAndNoIncompleteTasks()
    {
        $jobs = $this->getEntityRepository()->findBy(array(
            'state' => $this->getIncompleteStates()
        ));

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
     * @param int $limit
     *
     * @return Task[]
     */
    public function getQueuedTasks(Job $job, $limit = 1)
    {
        return $this->taskService->getEntityRepository()->findBy(
            [
                'job' => $job,
                'state' => $this->taskService->getQueuedState()
            ],
            [],
            $limit
        );
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getErroredTaskCount(Job $job)
    {
        $excludeStates = [
            $this->taskService->getCancelledState(),
            $this->taskService->getAwaitingCancellationState()
        ];

        return $this->taskService->getEntityRepository()->getErroredCountByJob($job, $excludeStates);
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getWarningedTaskCount(Job $job)
    {
        $excludeStates = [
            $this->taskService->getCancelledState(),
            $this->taskService->getAwaitingCancellationState()
        ];

        return $this->taskService->getEntityRepository()->getWarningedCountByJob($job, $excludeStates);
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getCancelledTaskCount(Job $job)
    {
        $states = [
            $this->taskService->getCancelledState(),
            $this->taskService->getAwaitingCancellationState()
        ];

        return $this->taskService->getEntityRepository()->getTaskCountByState($job, $states);
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getSkippedTaskCount(Job $job)
    {
        $states = [
            $this->taskService->getSkippedState()
        ];

        return $this->taskService->getEntityRepository()->getTaskCountByState($job, $states);
    }

    /**
     * @return State[]
     */
    public function getIncompleteStates()
    {
        $incompleteStates = [];

        foreach ($this->incompleteStateNames as $stateName) {
            $incompleteStates[] = $this->stateService->fetch($stateName);
        }

        return $incompleteStates;
    }

    /**
     * @return State[]
     */
    public function getFinishedStates()
    {
        return $this->stateService->getEntityRepository()->findAllStartingWithAndExcluding(
            'job-',
            $this->getIncompleteStates()
        );
    }

    /**
     * @return JobRepository
     */
    public function getEntityRepository()
    {
        return parent::getEntityRepository();
    }

    /**
     * @param int $jobId
     *
     * @return bool
     */
    public function getIsPublic($jobId)
    {
        return $this->getEntityRepository()->getIsPublicByJobId($jobId);
    }
}
