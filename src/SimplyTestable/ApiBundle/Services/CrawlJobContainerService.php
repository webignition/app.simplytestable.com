<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class CrawlJobContainerService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\CrawlJobContainer';

    const COMPLETED_STATE = 'crawl-completed';
    const IN_PROGRESS_STATE = 'crawl-in-progress';
    const QUEUED_STATE = 'crawl-queued';
    
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
     * @var \SimplyTestable\ApiBundle\Services\JobTypeService
     */
    private $jobTypeService;         
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\JobService
     */
    private $jobService;       
    
    
    public function __construct(
            EntityManager $entityManager,
            \SimplyTestable\ApiBundle\Services\StateService $stateService,
            \SimplyTestable\ApiBundle\Services\TaskService $taskService,
            \SimplyTestable\ApiBundle\Services\TaskTypeService $taskTypeService,
            \SimplyTestable\ApiBundle\Services\JobTypeService $jobTypeService,
            \SimplyTestable\ApiBundle\Services\JobService $jobService)
    {
        parent::__construct($entityManager);        
        $this->stateService = $stateService;
        $this->taskService = $taskService;
        $this->taskTypeService = $taskTypeService;
        $this->jobTypeService = $jobTypeService;
        $this->jobService = $jobService;
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
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return boolean
     */
    public function hasForJob(Job $job) {        
        return count($this->getEntityRepository()->findAllByJobAndStates($job, array(
            $this->getInProgressState(),
            $this->getQueuedState()
        ))) > 0;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return CrawlJob
     */
    public function getForJob(Job $job) {
        if (!$this->hasForJob($job)) {
            return null;
        }
        
        return $this->getEntityRepository()->findOneBy(array(
            'parentJob' => $job
        ));       
    }
    
    
    public function prepare(CrawlJobContainer $crawlJobContainer) {
        if (!$crawlJobContainer->getState()->equals($this->getQueuedState())) {
            return false;
        }
        
        if ($crawlJobContainer->getCrawlJob()->getTasks()->count() > 1) {
            return false;
        }        
        
        if ($crawlJobContainer->getCrawlJob()->getTasks()->count() === 1) {
            return true;
        }                
        
        $task = new Task();
        
        $task->setJob($crawlJobContainer->getCrawlJob());
        $task->setParameters(json_encode(array(
            'scope' => (string)$crawlJobContainer->getParentJob()->getWebsite()
        )));
        $task->setState($this->taskService->getQueuedState());
        $task->setType($this->taskTypeService->getByName('URL discovery'));
        $task->setUrl((string)$crawlJobContainer->getParentJob()->getWebsite());
        
        $crawlJobContainer->getCrawlJob()->addTask($task);
        $crawlJobContainer->getCrawlJob()->setState($this->jobService->getQueuedState());
        
        $timePeriod = new \SimplyTestable\ApiBundle\Entity\TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $crawlJobContainer->getCrawlJob()->setTimePeriod($timePeriod);          
        
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->persist($crawlJobContainer->getCrawlJob());
        $this->getEntityManager()->flush();
        
        return true;
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
    public function getQueuedState() {
        return $this->stateService->fetch(self::QUEUED_STATE);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return CrawlJobContainer
     */
    public function create(Job $job) {
        $crawlJob = new Job();
        $crawlJob->setType($this->jobTypeService->getCrawlType());
        $crawlJob->setState($this->jobService->getStartingState());
        $crawlJob->setUser($job->getUser());
        $crawlJob->setWebsite($job->getWebsite());
        
        $this->getEntityManager()->persist($crawlJob); 
        
        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setParentJob($job);
        $crawlJobContainer->setCrawlJob($crawlJob);
        $crawlJobContainer->setState($this->getQueuedState());

        return $this->persistAndFlush($crawlJobContainer);
    }
    
    
    /**
     *
     * @param CrawlJobContainer $crawlJobContainer
     * @return CrawlJobContainer
     */
    public function persistAndFlush(CrawlJobContainer $crawlJobContainer) {
        $this->getEntityManager()->persist($crawlJobContainer);
        $this->getEntityManager()->flush();
        return $crawlJobContainer;
    }

    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\CrawlJobContainerRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }    
}