<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;

class TaskService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Task\Task';
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
     * Collection of all the states a task could be in
     * 
     * @var array
     */
    private $availableStateNames = array(
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
    );
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StateService
     */
    private $stateService;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\ResqueQueueService 
     */
    private $resqueQueueService;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Repository\TaskOutputRepository
     */
    private $taskOutputRepository;
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     */
    public function __construct(
            EntityManager $entityManager,
            \SimplyTestable\ApiBundle\Services\StateService $stateService,
            \SimplyTestable\ApiBundle\Services\ResqueQueueService $resqueQueueService)
    {
        parent::__construct($entityManager);        
        $this->stateService = $stateService;
        $this->resqueQueueService = $resqueQueueService;
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
     * @param Task $task
     * @return \SimplyTestable\ApiBundle\Entity\Task\Task 
     */
    public function cancel(Task $task) { 
        if ($this->isFinished($task)) {
            return $task;
        }
        
        $task->setState($this->getCancelledState());
        $task->clearWorker();
        
        if ($task->getTimePeriod() instanceof TimePeriod) {
            $task->getTimePeriod()->setEndDateTime(new \DateTime());
        } else {
            $task->setTimePeriod(new TimePeriod());
            $task->getTimePeriod()->setStartDateTime(new \DateTime());
            $task->getTimePeriod()->setEndDateTime(new \DateTime());            
        }        
        
        $this->getEntityManager()->persist($task);    
        
        return $task;
    }
    
    
    public function setAwaitingCancellation(Task $task) {
        if ($this->isAwaitingCancellation($task)) {
            return $task;
        }          
        
        if ($this->isCancelled($task)) {
            return $task;
        }   
        
        if ($this->isCompleted($task)) {
            return $task;
        }
        
        $task->setState($this->getAwaitingCancellationState());      
        
        return $task;        
    }
    
    /**
     *
     * @param int $id
     * @return \SimplyTestable\ApiBundle\Entity\Task\Task
     */
    public function getById($id) {
        return $this->getEntityRepository()->find($id);
    }
    
    /**
     *
     * @return \SimlpyTestable\ApiBundle\Entity\State
     */
    public function getQueuedState() {
        return $this->stateService->fetch(self::QUEUED_STATE);
    }
    
    /**
     *
     * @return \SimlpyTestable\ApiBundle\Entity\State
     */
    public function getInProgressState() {
        return $this->stateService->fetch(self::IN_PROGRESS_STATE);
    }    
    
    
    /**
     *
     * @return \SimlpyTestable\ApiBundle\Entity\State
     */
    public function getCompletedState() {
        return $this->stateService->fetch(self::COMPLETED_STATE);
    }     
    
    
    /**
     *
     * @return \SimlpyTestable\ApiBundle\Entity\State
     */
    public function getCancelledState() {
        return $this->stateService->fetch(self::CANCELLED_STATE);
    }  
    
    
    /**
     *
     * @return \SimlpyTestable\ApiBundle\Entity\State
     */
    public function getQueuedForAssignmentState() {
        return $this->stateService->fetch(self::QUEUED_FOR_ASSIGNMENT_STATE);
    }      
    

    /**
     *
     * @return \SimlpyTestable\ApiBundle\Entity\State
     */
    public function getAwaitingCancellationState() {
        return $this->stateService->fetch(self::AWAITING_CANCELLATION_STATE);
    } 
    
    
    /**
     *
     * @return \SimlpyTestable\ApiBundle\Entity\State 
     */
    public function getFailedNoRetryAvailableState() {
        return $this->stateService->fetch(self::TASK_FAILED_NO_RETRY_AVAILABLE_STATE);
    }      
    
    
    /**
     *
     * @return \SimlpyTestable\ApiBundle\Entity\State 
     */
    public function getFailedRetryAvailableState() {
        return $this->stateService->fetch(self::TASK_FAILED_RETRY_AVAILABLE_STATE);
    }   
    
    
    /**
     *
     * @return \SimlpyTestable\ApiBundle\Entity\State 
     */
    public function getFailedRetryLimitReachedState() {
        return $this->stateService->fetch(self::TASK_FAILED_RETRY_LIMIT_REACHED_STATE);
    }     
    

    /**
     *
     * @return \SimlpyTestable\ApiBundle\Entity\State 
     */
    public function getSkippedState() {
        return $this->stateService->fetch(self::TASK_SKIPPED_STATE);
    }      
    
    
    /**
     *
     * @param Task $task
     * @return boolean
     */
    public function isCancelled(Task $task) {
        return $task->getState()->equals($this->getCancelledState());
    }
            
    /**
     *
     * @param Task $task
     * @return boolean
     */
    public function isAwaitingCancellation(Task $task) {
        return $task->getState()->equals($this->getAwaitingCancellationState());
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @return boolean
     */
    public function isCancellable(Task $task) {
        if ($this->isAwaitingCancellation($task)) {
            return true;
        }
        
        if ($this->isInProgress($task)) {
            return true;
        }
        
        if ($this->isQueued($task)) {
            return true;
        }
        
        if ($this->isQueuedForAssignment($task)) {
            return true;
        }
        
        return false;
    }
    
    
    /**
     *
     * @param Task $task
     * @return boolean
     */
    public function isCompleted(Task $task) {
        return $task->getState()->equals($this->getCompletedState());
    } 
    

    /**
     *
     * @param Task $task
     * @return boolean
     */
    public function isQueued(Task $task) {
        return $task->getState()->equals($this->getQueuedState());
    }    
    
    
    /**
     *
     * @param Task $task
     * @return boolean
     */
    public function isQueuedForAssignment(Task $task) {
        return $task->getState()->equals($this->getQueuedForAssignmentState());
    } 
    
    
    /**
     *
     * @param Task $task
     * @return boolean
     */
    public function isFailedNoRetryAvailable(Task $task) {
        return $task->getState()->equals($this->getFailedNoRetryAvailableState());
    }
    
    
    /**
     *
     * @param Task $task
     * @return boolean
     */
    public function isFailedRetryLimitReached(Task $task) {
        return $task->getState()->equals($this->getFailedRetryLimitReachedState());
    }    
    
    
    /**
     *
     * @param Task $task
     * @return boolean
     */
    public function isInProgress(Task $task) {        
        return $task->getState()->equals($this->getInProgressState());
    }
    
    
    /**
     *
     * @param Task $task
     * @return boolean
     */
    public function isSkipped(Task $task) {        
        return $task->getState()->equals($this->getSkippedState());
    }    
    
    
    /**
     *
     * @param Task $task
     * @return boolean 
     */
    public function isFinished(Task $task) {
        if ($this->isCompleted($task)) {
            return true;
        }
        
        if ($this->isCancelled($task)) {
            return true;
        }
        
        if ($this->isFailedNoRetryAvailable($task)) {
            return true;
        }
        
        if ($this->isFailedRetryLimitReached($task)) {
            return true;
        }  
        
        if ($this->isSkipped($task)) {
            return true;
        }
        
        return false;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getIncompleteStates() {
        return array(
            $this->getInProgressState(),
            $this->getQueuedState(),
            $this->getQueuedForAssignmentState()            
        );
    }
    
    /**
     *
     * @param Task $task
     * @return Task
     */
    public function persist(Task $task) {
        $this->getEntityManager()->persist($task);
        return $task;
    }     
    
    
    /**
     *
     * @param Task $task
     * @return Task
     */
    public function persistAndFlush(Task $task) {
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();
        return $task;
    } 
    
    
    /**
     *
     * @param Task $task
     * @param Worker $worker
     * @param int $remoteId
     */
    public function setStarted(Task $task, Worker $worker, $remoteId) {
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());

        $task->setRemoteId($remoteId);
        $task->setState($this->getInProgressState());
        $task->setTimePeriod($timePeriod);
        $task->setWorker($worker);        
    }
    
    
    /**
     *
     * @param int $remoteId
     * @return \SimplyTestable\ApiBundle\Entity\Task\Task
     */
    public function getByRemoteId($remoteId) {
        return $this->getEntityRepository()->findBy(array(
            'remoteId' => $remoteId
        ));
    } 
    
    
    /**
     *
     * @param Task $task
     * @param \DateTime $endDateTime
     * @param \SimplyTestable\ApiBundle\Entity\Task\Output $output
     * @param \SimlpyTestable\ApiBundle\Entity\State $state
     * @return \SimplyTestable\ApiBundle\Entity\Task\Task 
     */
    public function complete(Task $task, \DateTime $endDateTime, TaskOutput $output, State $state) {
        $taskIsInCorrectState = false;
        foreach ($this->getIncompleteStates() as $incompleteState) {
            if ($task->getState()->equals($incompleteState)) {
                $taskIsInCorrectState = true;
            }
        }
        
        if (!$taskIsInCorrectState) {
            return $task;
        }
        
        $output->generateHash();        
        $existingOutput = $this->getTaskOutputEntityRepository()->findOutputByhash($output->getHash());
        
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
     * 
     * @return \SimplyTestable\ApiBundle\Repository\TaskOutputRepository
     */
    private function getTaskOutputEntityRepository() {
        if (is_null($this->taskOutputRepository)) {
            $this->taskOutputRepository = $this->entityManager->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output');
        }
        
        return $this->taskOutputRepository;
    }
    
    
    /**
     *
     * @param Worker $worker
     * @param int $remoteId
     * @return Task
     */
    public function getByWorkerAndRemoteId(Worker $worker, $remoteId) {        
        $tasks = $this->getEntityRepository()->findBy(array(
            'worker' => $worker,
            'remoteId' => $remoteId
        ), array(
            'id' => 'DESC'
        ), 1);
        
        if (count($tasks) === 0) {
            return null;
        }
        
        return $tasks[0];
    }
    
    /**
     *
     * @param Job $job
     * @return int
     */
    public function getUrlCountByJob(Job $job) {
        return $this->getEntityRepository()->findUrlCountByJob($job); 
    }
    
    
    /**
     *
     * @param Job $job
     * @return array 
     */
    public function getUrlsByJob(Job $job) {
        return $this->getEntityRepository()->findUrlsByJob($job); 
    }
    
    
    /**   
     * 
     * @param Job $job
     * @return array 
     */
    public function getAwaitingCancellationByJob(Job $job) {
        return $this->getEntityRepository()->findBy(array(
            'job' => $job,
            'state' => $this->getAwaitingCancellationState()
        ));
    }
    
    
    /**
     *
     * @return array
     */
    public function getJobsWithQueuedTasks() {
        return $this->getEntityRepository()->findJobsbyTaskState($this->getQueuedState());      
    }
    
    
    /**
     *
     * @param TaskType $taskType
     * @param State $state
     * @return int
     */
    public function getCountByTaskTypeAndState(TaskType $taskType, State $state) {
        return $this->getEntityRepository()->getCountByTaskTypeAndState($taskType, $state);        
    }
    
    
    /**
     *
     * @param Job $job
     * @param State $state
     * @return int 
     */
    public function getCountByJobAndState(Job $job, State $state) {
        return $this->getEntityRepository()->getCountByJobAndState($job, $state);
    }   
    
    
    /**
     *
     * @param Job $job
     * @return integer 
     */
    public function getCountByJob(Job $job) {
        return $this->getEntityRepository()->getCountByJob($job);
    }
    
    
    /**
     *
     * @return array
     */
    public function getAvailableStateNames() {
        return $this->availableStateNames;
    }
    
    /**
     * 
     * @return boolean
     */
    public function hasQueuedTasks() {        
        return $this->getQueuedCount() > 0;
    }
    
    
    /**
     * 
     * @return int
     */
    public function getQueuedCount() {
        return $this->getEntityRepository()->getCountByState($this->getQueuedState());
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\TaskRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }  
    
    
    /**
     * 
     * @param string $url
     * @param \SimplyTestable\ApiBundle\Entity\Task\Type\Type $taskType
     * @param string $parameter_hash
     * @param array $states
     * @return array
     */
    public function getEquivalentTasks($url, TaskType $taskType, $parameter_hash, $states) {
        $tasks = $this->getEntityRepository()->getCollectionByUrlAndTaskTypeAndStates(
            $url,
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