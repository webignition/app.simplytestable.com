<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;

class JobService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\Job';
    const STARTING_STATE = 'job-new';
    const CANCELLED_STATE = 'job-cancelled';
    const COMPLETED_STATE = 'job-completed';
    const IN_PROGRESS_STATE = 'job-in-progress';
    
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
     * @param User $user
     * @param WebSite $website
     * @param array $taskTypes
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job 
     */
    public function create(User $user, WebSite $website, array $taskTypes) {
        $job = new Job();
        $job->setUser($user);
        $job->setWebsite($website);
        
        foreach ($taskTypes as $taskType) {
            if ($taskType instanceof TaskType) {
                $job->addRequestedTaskType($taskType);
            }
        }
        
        $statesToCheckFor = array(
            'job-in-progress',
            'job-queued',
            'job-new'
        );
        
        foreach($statesToCheckFor as $stateToCheckFor) {
            $job->setState($this->stateService->fetch($stateToCheckFor));
            if ($this->has($job)) {                
                $this->cancel($this->fetch($job));
            }
        }
        
        $job->setState($this->stateService->fetch(self::STARTING_STATE));
        return $this->persistAndFlush($job);
    }
    
    
    /**
     *
     * @param Job $job
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function cancel(Job $job) {
        $tasks = $job->getTasks();        
        
        /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
        foreach ($tasks as $task) {            
            $this->taskService->cancel($task);
        }
        
        if ($job->getTimePeriod() instanceof TimePeriod) {
            $job->getTimePeriod()->setEndDateTime(new \DateTime());
        } else {
            $job->setTimePeriod(new TimePeriod());
            $job->getTimePeriod()->setStartDateTime(new \DateTime());
            $job->getTimePeriod()->setEndDateTime(new \DateTime());            
        }
        
        $job->setState($this->stateService->fetch(self::CANCELLED_STATE));
        return $this->persistAndFlush($job);
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
        foreach ($job->getTasks() as $task) {
            /* @var $task Task */
            if (!$task->getState()->equals($this->taskService->getCompletedState())) {
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
        if (!$job->getState()->equals($this->getInProgressState())) {
            return $job;
        }        
        
        if ($this->hasIncompleteTasks($job)) {
            return $job;
        }
        
        $job->getTimePeriod()->setEndDateTime(new \DateTime());        
        $job->setNextState();       
        
        return $this->persistAndFlush($job);
    }
}