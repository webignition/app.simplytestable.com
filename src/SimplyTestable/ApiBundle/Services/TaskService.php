<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class TaskService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Task\Task';
    const STARTING_STATE = 'task-new';
    const CANCELLED_STATE = 'task-cancelled';
    const QUEUED_STATE = 'task-queued';
    const IN_PROGRESS_STATE = 'task-in-progress';
    const COMPLETED_STATE = 'task-completed';
    const AWAITING_CANCELLATION_STATE = 'task-awaiting-cancellation';
    
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
        if ($this->isCompleted($task)) {
            return $task;
        }
        
        if ($this->isCancelled($task)) {
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
        $this->getEntityManager()->flush();      
        
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
    public function getStartingState() {
        return $this->stateService->fetch(self::STARTING_STATE);
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
    public function getAwaitingCancellationState() {
        return $this->stateService->fetch(self::AWAITING_CANCELLATION_STATE);
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
    public function isInProgress(Task $task) {
        return $task->getState()->equals($this->getInProgressState());
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
     * @return Task 
     */
    public function setStarted(Task $task, Worker $worker, $remoteId) {
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());

        $task->setRemoteId($remoteId);
        $task->setNextState();
        $task->setTimePeriod($timePeriod);
        $task->setWorker($worker);        
        
        return $this->persistAndFlush($task);
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
     * @return \SimplyTestable\ApiBundle\Entity\Task\Task 
     */
    public function complete(Task $task, \DateTime $endDateTime, TaskOutput $output) {
        if (!$this->isInProgress($task)) {
            return $task;
        }        

        $task->getTimePeriod()->setEndDateTime($endDateTime);
        $task->setOutput($output);
        $task->setNextState();            
        $task->clearWorker();
        $task->clearRemoteId();

        return $this->persistAndFlush($task);
    }
    
    
    /**
     *
     * @param Worker $worker
     * @param int $remoteId
     * @return Task
     */
    public function getByWorkerAndRemoteId(Worker $worker, $remoteId) {
        return $this->getEntityRepository()->findOneBy(array(
            'worker' => $worker,
            'remoteId' => $remoteId
        ));
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
    
    
  
}