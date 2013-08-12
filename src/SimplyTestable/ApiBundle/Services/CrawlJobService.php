<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Job\CrawlJob;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;

class CrawlJobService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\CrawlJob';

    const COMPLETED_STATE = 'crawl-completed';
    const IN_PROGRESS_STATE = 'crawl-in-progress';
    const QUEUED_STATE = 'crawl-queued';
    
    private $incompleteStateNames = array(
        self::IN_PROGRESS_STATE,
        self::QUEUED_STATE
    );
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StateService
     */
    private $stateService;
    
    
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     */
    public function __construct(
            EntityManager $entityManager,
            \SimplyTestable\ApiBundle\Services\StateService $stateService)
    {
        parent::__construct($entityManager);        
        $this->stateService = $stateService;
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
    
    
//    /**
//     *
//     * @return \SimplyTestable\ApiBundle\Services\StateService
//     */
//    public function getStateService() {
//        return $this->stateService;                
//    }
    
    
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
    public function getQueuedState() {
        return $this->stateService->fetch(self::QUEUED_STATE);
    }
    
    
//    /**
//     *
//     * @param Job $job
//     * @return boolean
//     */
//    public function isNew(Job $job) {
//        return $job->getState()->equals($this->getQueuedState());
//    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     */
    public function create(Job $job) {        
        var_dump("cp01");
        exit();
        
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
                    $job->getTaskTypeOptions()->add($taskTypeOptions);
                }
            }
        }
        
        $job->setState($this->getStartingState());
        $this->getEntityManager()->persist($job);        
        
        $this->getEntityManager()->flush();

        return $job;
    }    
    
    
//    /**
//     *
//     * @param Job $job
//     * @return boolean 
//     */
//    private function isFinished(Job $job) {
//        if ($this->isCancelled($job)) {
//            return true;
//        }
//        
//        if ($this->isCompleted($job)) {
//            return true;
//        }    
//        
//        if ($this->isFailedNoSitemap($job)) {
//            return true;
//        }
//        
//        return false;
//    }

    
    
//    /**
//     *
//     * @param Job $job
//     * @return boolean 
//     */
//    private function isCompleted(Job $job) {
//        return $job->getState()->equals($this->getCompletedState());
//    }    
    

//    /**
//     *
//     * @param Job $job
//     * @return boolean 
//     */
//    private function isInProgress(Job $job) {
//        return $job->getState()->equals($this->getInProgressState());
//    }     
    
      
    
    
//    /**
//     *
//     * @param Job $job
//     * @return \SimplyTestable\ApiBundle\Entity\Job\Job|boolean 
//     */
//    public function fetch(Job $job) {        
//        $jobs = $this->getEntityRepository()->findBy(array(
//            'state' => $job->getState(),
//            'user' => $job->getUser(),
//            'website' => $job->getWebsite()
//        ));        
//        
//        /* @var $comparator Job */
//        foreach ($jobs as $comparator) {
//            if ($job->equals($comparator)) {
//                return $comparator;
//            }
//        }
//        
//        return false;  
//    }
    
    
//    /**
//     *
//     * @param Job $job
//     * @return boolean
//     */
//    public function has(Job $job) {        
//        return $this->fetch($job) !== false;
//    }
    
    
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
    
    
//    /**
//     * Does a job have any tasks which have not yet completed?
//     * 
//     * @param Job $job
//     * @return boolean 
//     */
//    public function hasIncompleteTasks(Job $job) {
//        $incompleteTaskStates = $this->taskService->getIncompleteStates();
//        foreach ($incompleteTaskStates as $state) {
//            $taskCount = $this->taskService->getCountByJobAndState($job, $state);
//            if ($taskCount > 0) {
//                return true;
//            }
//        }
//        
//        return false;
//    }
            
    
//    /**
//     *
//     * @param Job $job
//     * @return \SimplyTestable\ApiBundle\Entity\Job\Job 
//     */
//    public function complete(Job $job) {
//        if (!$this->isInProgress($job)) {
//            return $job;
//        }        
//        
//        if ($this->hasIncompleteTasks($job)) {
//            return $job;
//        }
//        
//        $job->getTimePeriod()->setEndDateTime(new \DateTime());        
//        $job->setNextState();       
//        
//        return $this->persistAndFlush($job);
//    }


    
    
//    /**
//     * 
//     * @return array
//     */
//    public function getIncompleteStates() {
//        $incompleteStates = array();
//        
//        foreach ($this->incompleteStateNames as $stateName) {
//            $incompleteStates[] = $this->stateService->fetch($stateName);
//        }
//        
//        return $incompleteStates;      
//    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\JobRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }    
}