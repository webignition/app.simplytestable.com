<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason as JobRejectionReason;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class JobService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\Job';
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
    
    private $incompleteStateNames = array(
        self::STARTING_STATE,
        self::RESOLVING_STATE,
        self::RESOLVED_STATE,
        self::IN_PROGRESS_STATE,
        self::PREPARING_STATE,
        self::QUEUED_STATE
    );
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StateService
     */
    private $stateService;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\TaskService
     */
    private $taskService;      
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\TaskTypeService
     */
    private $taskTypeService;
    
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     * @param \SimplyTestable\ApiBundle\Services\TaskService $taskService
     * @param \SimplyTestable\ApiBundle\Services\TaskTypeService $taskTypeService
     */
    public function __construct(
            EntityManager $entityManager,
            \SimplyTestable\ApiBundle\Services\StateService $stateService,
            \SimplyTestable\ApiBundle\Services\TaskService $taskService,
            \SimplyTestable\ApiBundle\Services\TaskTypeService $taskTypeService)
    {
        parent::__construct($entityManager);        
        $this->stateService = $stateService;
        $this->taskService = $taskService;
        $this->taskTypeService = $taskTypeService;
    }
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     *
     * @param int $id
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function getById($id) {
        return $this->getEntityRepository()->find($id);
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\StateService
     */
    public function getStateService() {
        return $this->stateService;                
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getCompletedState() {
        return $this->stateService->fetch(self::COMPLETED_STATE);
    }

    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getInProgressState() {
        return $this->stateService->fetch(self::IN_PROGRESS_STATE);
    }
    
        
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getCancelledState() {
        return $this->stateService->fetch(self::CANCELLED_STATE);
    } 
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getStartingState() {
        return $this->stateService->fetch(self::STARTING_STATE);
    }  
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getPreparingState() {
        return $this->stateService->fetch(self::PREPARING_STATE);
    }  
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getQueuedState() {
        return $this->stateService->fetch(self::QUEUED_STATE);
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getFailedNoSitemapState() {
        return $this->stateService->fetch(self::FAILED_NO_SITEMAP_STATE);
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getRejectedState() {
        return $this->stateService->fetch(self::REJECTED_STATE);
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getResolvingState() {
        return $this->stateService->fetch(self::RESOLVING_STATE);
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getResolvedState() {
        return $this->stateService->fetch(self::RESOLVED_STATE);
    }    

    
    /**
     *
     * @param Job $job
     * @return boolean
     */
    public function isNew(Job $job) {
        return $job->getState()->equals($this->getStartingState());
    }    
    
    /**
     *
     * @param Job $job
     * @return boolean
     */
    public function isPreparing(Job $job) {
        return $job->getState()->equals($this->getPreparingState());
    }
    
    /**
     *
     * @param Job $job
     * @return boolean
     */
    public function isQueued(Job $job) {
        return $job->getState()->equals($this->getQueuedState());
    }    
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return boolean
     */
    public function isRejected(Job $job) {
        return $job->getState()->equals($this->getRejectedState());
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return boolean
     */
    public function isFailedNoSitepmap(Job $job) {
        return $job->getState()->equals($this->getFailedNoSitemapState());
    }
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return boolean
     */
    public function isCompleted(Job $job) {
        return $job->getState()->equals($this->getCompletedState());
    } 
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return boolean
     */
    public function isResolved(Job $job) {
        return $job->getState()->equals($this->getResolvedState());
    }    
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return boolean
     */
    public function isResolving(Job $job) {
        return $job->getState()->equals($this->getResolvingState());
    } 


    /**
     * @param JobConfiguration $jobConfiguration
     * @return Job
     */
    public function create(JobConfiguration $jobConfiguration) {
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
            $job->setParameters(json_encode($jobConfiguration->getParameters()));
        }

        $job->setState($this->getStartingState());
        $this->getManager()->persist($job);
        $this->getManager()->flush();

        return $job;
    }
    

    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @param string $reason
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint $constraint
     */
    public function addAmmendment(Job $job, $reason, \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint $constraint = null) {        
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
     *
     * @param Job $job
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function cancel(Job $job) {
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
    
    
    public function cancelIncompleteTasks(Job $job) {
        foreach ($job->getTasks() as $task) {
            if (!$this->taskService->isCompleted($task)) {
                $this->taskService->cancel($task);
            }           
        }        
    }
    
    
    /**
     *
     * @param Job $job
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function reject(Job $job) {        
        if (!$this->isNew($job) && !$this->isPreparing($job) && !$this->isResolving($job)) {
            return $job;
        }
        
        $job->setState($this->getRejectedState());
        return $this->persistAndFlush($job);
    }    
    
    
    
    /**
     *
     * @param Job $job
     * @return boolean 
     */
    public function isFinished(Job $job) {
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
     *
     * @param Job $job
     * @return boolean 
     */
    private function isCancelled(Job $job) {
        return $job->getState()->equals($this->getCancelledState());
    }
    
    
    /**
     *
     * @param Job $job
     * @return boolean 
     */
    private function isFailedNoSitemap(Job $job) {
        return $job->getState()->equals($this->getFailedNoSitemapState());
    }        
    
    
    /**
     *
     * @param Job $job
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job|boolean 
     */
    public function fetch(Job $job) {        
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
     *
     * @param Job $job
     * @return boolean
     */
    public function has(Job $job) {        
        return $this->fetch($job) !== false;
    }
    
    
    /**
     *
     * @param Job $job
     * @return Job
     */
    public function persistAndFlush(Job $job) {
        $this->getManager()->persist($job);
        $this->getManager()->flush();
        return $job;
    }
    
    
    /**
     * Does a job have any tasks which have not yet completed?
     * 
     * @param Job $job
     * @return boolean 
     */
    public function hasIncompleteTasks(Job $job) {
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
     *
     * @param Job $job
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job 
     */
    public function complete(Job $job) {
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
     *
     * @return array
     */
    public function getJobsWithQueuedTasks() {
        $states = array(
            $this->getInProgressState(),
            $this->getPreparingState(),
            $this->getQueuedState()
        );
        
        $jobs = $this->getEntityRepository()->getByStateAndTaskState($states, $this->taskService->getQueuedState());
        
        return $jobs;
    }
    
    
    public function getUnfinishedJobsWithTasksAndNoIncompleteTasks() {
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
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @param int $limit
     * @return array
     */
    public function getQueuedTasks(Job $job, $limit = 1) {
        return $this->taskService->getEntityRepository()->findBy(array(
            'job' => $job,
            'state' => $this->taskService->getQueuedState()
        ),
        array(),
        $limit);
    }
    
    
    /**
     * Get the number of tasks that have errors
     * i.e. how many tasks have errors?
     * 
     * @param Job $job
     * @return int
     */
    public function getErroredTaskCount(Job $job) {
        $excludeStates = array(
            $this->taskService->getCancelledState(),
            $this->taskService->getAwaitingCancellationState()
        );        
        
        return $this->taskService->getEntityRepository()->getErroredCountByJob($job, $excludeStates);
    }      
    
    
    /**
     * Get the number of tasks that have warnings
     * i.e. how many tasks have warnings?
     * 
     * @param Job $job
     * @return int
     */
    public function getWarningedTaskCount(Job $job) {
        $excludeStates = array(
            $this->taskService->getCancelledState(),
            $this->taskService->getAwaitingCancellationState()
        );        
        
        return $this->taskService->getEntityRepository()->getWarningedCountByJob($job, $excludeStates);
    }    
    
    
    /**
     *
     * @param Job $job
     * @return int 
     */
    public function getCancelledTaskCount(Job $job) {
        $states = array(
            $this->taskService->getCancelledState(),
            $this->taskService->getAwaitingCancellationState()
        );
        
        return $this->taskService->getEntityRepository()->getTaskCountByState($job, $states);
    }
    
    
    /**
     *
     * @param Job $job
     * @return int 
     */
    public function getSkippedTaskCount(Job $job) {
        $states = array(
            $this->taskService->getSkippedState()
        );
        
        return $this->taskService->getEntityRepository()->getTaskCountByState($job, $states);
    }
    
    
    
    /**
     * 
     * @return array
     */
    public function getIncompleteStates() {
        $incompleteStates = array();
        
        foreach ($this->incompleteStateNames as $stateName) {
            $incompleteStates[] = $this->stateService->fetch($stateName);
        }
        
        return $incompleteStates;      
    }
    
    
    public function getFinishedStates() {
        return $this->stateService->getEntityRepository()->findAllStartingWithAndExcluding('job-', $this->getIncompleteStates());
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\JobRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }
    
    
    /**
     * 
     * @param int $jobId
     * @return boolean
     */
    public function getIsPublic($jobId) {
        return $this->getEntityRepository()->getIsPublicByJobId($jobId);
    }
}