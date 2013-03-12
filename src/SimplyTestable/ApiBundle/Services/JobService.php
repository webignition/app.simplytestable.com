<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;

class JobService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\Job';
    const STARTING_STATE = 'job-new';
    const CANCELLED_STATE = 'job-cancelled';
    const COMPLETED_STATE = 'job-completed';
    const IN_PROGRESS_STATE = 'job-in-progress';
    const PREPARING_STATE = 'job-preparing';
    const QUEUED_STATE = 'job-queued';
    const NO_SITEMAP_STATE = 'job-no-sitemap';
    
    private $incompleteStateNames = array(
        self::STARTING_STATE,
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
     * @param EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     * @param \SimplyTestable\ApiBundle\Services\TaskService $taskService
     */
    public function __construct(
            EntityManager $entityManager,
            \SimplyTestable\ApiBundle\Services\StateService $stateService,
            \SimplyTestable\ApiBundle\Services\TaskService $taskService)
    {
        parent::__construct($entityManager);        
        $this->stateService = $stateService;
        $this->taskService = $taskService;
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
    public function getNoSitemapState() {
        return $this->stateService->fetch(self::NO_SITEMAP_STATE);
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
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @param \SimplyTestable\ApiBundle\Entity\WebSite $website
     * @param array $taskTypes
     * @param array $taskTypeOptionsArray
     * @param \SimplyTestable\ApiBundle\Services\JobType $type
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function create(User $user, WebSite $website, array $taskTypes, array $taskTypeOptionsArray, JobType $type) {        
        $job = new Job();
        $job->setUser($user);
        $job->setWebsite($website);
        $job->setType($type);
        
        foreach ($taskTypes as $taskType) {
            if ($taskType instanceof TaskType) {                
                $job->addRequestedTaskType($taskType);
                $comparatorTaskTypeName = strtolower($taskType->getName());                
                
                if (isset($taskTypeOptionsArray[$comparatorTaskTypeName])) {
                    $taskTypeOptions = new TaskTypeOptions();
                    $taskTypeOptions->setJob($job);
                    $taskTypeOptions->setTaskType($taskType);
                    $taskTypeOptions->setOptions($taskTypeOptionsArray[$comparatorTaskTypeName]);                

                    $this->getEntityManager()->persist($taskTypeOptions);                    
                }
            }
        }
        
        $job->setState($this->getStartingState());
        $this->getEntityManager()->persist($job);
        
        $this->getEntityManager()->flush();

        return $job;
    }
    
    
    /**
     *
     * @param Job $job
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function cancel(Job $job) {
        if ($this->isFinished($job)) {
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
     *
     * @param Job $job
     * @return boolean 
     */
    private function isFinished(Job $job) {
        if ($this->isCancelled($job)) {
            return true;
        }
        
        if ($this->isCompleted($job)) {
            return true;
        }    
        
        if ($this->hasNoSitemap($job)) {
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
    private function isCompleted(Job $job) {
        return $job->getState()->equals($this->getCompletedState());
    }    
    

    /**
     *
     * @param Job $job
     * @return boolean 
     */
    private function isInProgress(Job $job) {
        return $job->getState()->equals($this->getInProgressState());
    }     
    
    
    /**
     *
     * @param Job $job
     * @return boolean 
     */
    private function hasNoSitemap(Job $job) {
        return $job->getState()->equals($this->getNoSitemapState());
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
        $this->getEntityManager()->persist($job);
        $this->getEntityManager()->flush();
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
        if (!$this->isInProgress($job)) {
            return $job;
        }        
        
        if ($this->hasIncompleteTasks($job)) {
            return $job;
        }
        
        $job->getTimePeriod()->setEndDateTime(new \DateTime());        
        $job->setNextState();       
        
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
        
        return $this->taskService->getEntityRepository()->getErrorCountByJob($job, $excludeStates);
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
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\JobRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }    
}