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
        
        $task = $this->createUrlDiscoveryTask($crawlJobContainer, (string)$crawlJobContainer->getParentJob()->getWebsite());
        
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
    
    
    private function createUrlDiscoveryTask(CrawlJobContainer $crawlJobContainer, $url) {
        $task = new Task();
        
        $task->setJob($crawlJobContainer->getCrawlJob());
        $task->setParameters(json_encode(array(
            'scope' => (string)$crawlJobContainer->getParentJob()->getWebsite()
        )));
        $task->setState($this->taskService->getQueuedState());
        $task->setType($this->taskTypeService->getByName('URL discovery'));
        $task->setUrl($url);
        
        return $task;
    }
    
    
    public function processTaskResults(Task $task) {
        if (is_null($task->getType())) {
            return false;
        }
        
        if (!$task->getType()->equals($this->taskTypeService->getByName('URL discovery'))) {
            return false;
        }
        
        if (is_null($task->getState())) {
            return false;
        } 
        
        if (!$task->getState()->equals($this->taskService->getCompletedState())) {
            return false;
        }
        
        if (is_null($task->getOutput())) {
            return false;
        }
        
        if ($task->getOutput()->getErrorCount() > 0) {
            return false;
        }
        
        $crawlJobContainer = $this->getEntityRepository()->findOneBy(array(
            'crawlJob' => $task->getJob()
        ));
      
        $discoveredUrlSet = json_decode($task->getOutput()->getOutput());
        $isFlushRequired = false;
        
        foreach ($discoveredUrlSet as $url) {            
            if (!$this->isTaskUrl($crawlJobContainer, $url)) {
                $task = $this->createUrlDiscoveryTask($crawlJobContainer, $url);
                $this->getEntityManager()->persist($task);
                $crawlJobContainer->getCrawlJob()->addTask($task);
                $isFlushRequired = true;
            }
        }
        
        if ($isFlushRequired) {
            $this->getEntityManager()->persist($crawlJobContainer->getCrawlJob());
            $this->getEntityManager()->flush();            
        }
        
        return true;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\CrawlJobContainer $crawlJobContainer
     * @return array
     */
    public function getProcessedUrls(CrawlJobContainer $crawlJobContainer) {
        return $this->taskService->getEntityRepository()->findUrlsByJobAndState(
            $crawlJobContainer->getCrawlJob(),
            $this->taskService->getCompletedState()
        );
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\CrawlJobContainer $crawlJobContainer
     * @return array
     */
    public function getDiscoveredUrls(CrawlJobContainer $crawlJobContainer) {
        $discoveredUrls = array();
        
        $completedTasks = $this->taskService->getEntityRepository()->findBy(array(
            'job' => $crawlJobContainer->getCrawlJob(),
            'state' => $this->taskService->getCompletedState()
        ));
        
        foreach ($completedTasks as $task) {
            if ($task->getOutput()->getErrorCount() === 0) {
                if (!in_array($task->getUrl(), $discoveredUrls)) {
                    $discoveredUrls[] = $task->getUrl();
                }                
                
                $urlSet = json_decode($task->getOutput()->getOutput());
                
                foreach ($urlSet as $url) {
                    if (!in_array($url, $discoveredUrls)) {
                        $discoveredUrls[] = $url;
                    }
                }
            }
        }
        
        return $discoveredUrls;
    }    
    
    private function isTaskUrl(CrawlJobContainer $crawlJobContainer, $url) {
        $url = (string)new \webignition\NormalisedUrl\NormalisedUrl($url);
        return $this->taskService->getEntityRepository()->findUrlExistsByJobAndUrl(
            $crawlJobContainer->getCrawlJob(),
            $url
        );      
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