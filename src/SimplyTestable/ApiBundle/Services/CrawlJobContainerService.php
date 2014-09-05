<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\ApiBundle\Entity\User;

class CrawlJobContainerService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\CrawlJobContainer';
    
    
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
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService
     */
    private $jobUserAccountPlanEnforcementService;        
    
    
    public function __construct(
            EntityManager $entityManager,
            \SimplyTestable\ApiBundle\Services\TaskService $taskService,
            \SimplyTestable\ApiBundle\Services\TaskTypeService $taskTypeService,
            \SimplyTestable\ApiBundle\Services\JobTypeService $jobTypeService,
            \SimplyTestable\ApiBundle\Services\JobService $jobService,
            \SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService)
    {
        parent::__construct($entityManager);        
        $this->taskService = $taskService;
        $this->taskTypeService = $taskTypeService;
        $this->jobTypeService = $jobTypeService;
        $this->jobService = $jobService;
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
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
        return $this->getEntityRepository()->hasForJob($job);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return CrawlJobContainer
     */
    public function getForJob(Job $job) {
        if (!$this->hasForJob($job)) {
            return $this->create($job);
        }
        
        return $this->getEntityRepository()->getForJob($job);      
    }
    
    
    public function prepare(CrawlJobContainer $crawlJobContainer) {                
        if ($crawlJobContainer->getCrawlJob()->getTasks()->count() > 1) {
            return false;
        }        
        
        if ($crawlJobContainer->getCrawlJob()->getTasks()->count() === 1) {
            return true;
        }                
        
        if (!$this->jobService->isNew($crawlJobContainer->getCrawlJob())) {
            return false;
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
        $parentCanonicalUrl = new \webignition\NormalisedUrl\NormalisedUrl($crawlJobContainer->getParentJob()->getWebsite()->getCanonicalUrl());
        
        $scope = array(
            (string)$parentCanonicalUrl
        );
        
        $hostParts = $parentCanonicalUrl->getHost()->getParts();
        if ($hostParts[0] === 'www') {
            $variant = clone $parentCanonicalUrl;            
            $variant->setHost(implode('.', array_slice($parentCanonicalUrl->getHost()->getParts(), 1)));            
            $scope[] = (string)$variant;
        } else {
            $variant = new \webignition\NormalisedUrl\NormalisedUrl($parentCanonicalUrl);
            $variant->setHost('www.' . (string)$variant->getHost());            
            $scope[] = (string)$variant;
        }
        
        $parameters = array(
            'scope' => $scope
        );
        
        if ($crawlJobContainer->getCrawlJob()->hasParameters()) {
            $parameters = array_merge($parameters, json_decode($crawlJobContainer->getCrawlJob()->getParameters(), true));
        }
        
        $task = new Task();        
        $task->setJob($crawlJobContainer->getCrawlJob());        
        $task->setParameters(json_encode($parameters));
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
        
        /* @var $crawlJobContainer \SimplyTestable\ApiBundle\Entity\CrawlJobContainer */
        $crawlJobContainer = $this->getEntityRepository()->findOneBy(array(
            'crawlJob' => $task->getJob()
        ));
        
        $taskDiscoveredUrlSet = $this->getDiscoveredUrlsFromTask($task);
        if (!count($taskDiscoveredUrlSet) === 0) {
            return true;
        }
        
        $this->jobUserAccountPlanEnforcementService->setUser($crawlJobContainer->getCrawlJob()->getUser());
        $crawlDiscoveredUrlCount = count($this->getDiscoveredUrls($crawlJobContainer));

        return $crawlDiscoveredUrlCount;
        
        if ($this->jobUserAccountPlanEnforcementService->isJobUrlLimitReached($crawlDiscoveredUrlCount)) {            
            if ($crawlJobContainer->getCrawlJob()->getAmmendments()->isEmpty()) {
                $this->jobService->addAmmendment($crawlJobContainer->getCrawlJob(), 'plan-url-limit-reached:discovered-url-count-' . $crawlDiscoveredUrlCount, $this->jobUserAccountPlanEnforcementService->getJobUrlLimitConstraint());            
                $this->jobService->persistAndFlush($crawlJobContainer->getCrawlJob());
            }
            
            if (!$this->jobService->isCompleted($crawlJobContainer->getCrawlJob())) {
                $this->jobService->cancelIncompleteTasks($crawlJobContainer->getCrawlJob());
                $this->taskService->getEntityManager()->flush();
            }
     
            return true;
        }
        
        $isFlushRequired = false;
        
        foreach ($taskDiscoveredUrlSet as $url) {            
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
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @return array
     */
    private function getDiscoveredUrlsFromTask(Task $task) {
        $taskDiscoveredUrlSet = json_decode($task->getOutput()->getOutput());
        return (is_array($taskDiscoveredUrlSet)) ? $taskDiscoveredUrlSet : array();
    }


    /**
     * @param TaskOutput $taskOutput
     * @return array
     */
    private function getDiscoveredUrlsFromTaskOutput(TaskOutput $taskOutput) {
        $taskDiscoveredUrlSet = json_decode($taskOutput->getOutput());
        return (is_array($taskDiscoveredUrlSet)) ? $taskDiscoveredUrlSet : array();
    }


    /**
     * @param string $taskOutput
     * @return array
     */
    private function getDiscoveredUrlsFromRawTaskOutput($taskOutput) {
        $taskDiscoveredUrlSet = json_decode($taskOutput);
        return (is_array($taskDiscoveredUrlSet)) ? $taskDiscoveredUrlSet : array();
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
    public function getDiscoveredUrls(CrawlJobContainer $crawlJobContainer, $constrainToAccountPlan = false) {
        $discoveredUrls = array(
            $crawlJobContainer->getParentJob()->getWebsite()->getCanonicalUrl()
        );

        $completedTaskUrls = $this->taskService->getEntityRepository()->findUrlsByJobAndState(
            $crawlJobContainer->getCrawlJob(),
            $this->taskService->getCompletedState()
        );

        foreach ($completedTaskUrls as $taskUrl) {
            if (!in_array($taskUrl, $discoveredUrls)) {
                $discoveredUrls[] = $taskUrl;
            }
        }

        $completedTaskOutputs = $this->taskService->getEntityRepository()->getOutputCollectionByJobAndState(
            $crawlJobContainer->getCrawlJob(),
            $this->taskService->getCompletedState()
        );

        foreach ($completedTaskOutputs as $taskOutput) {
            $urlSet = $this->getDiscoveredUrlsFromRawTaskOutput($taskOutput);

            foreach ($urlSet as $url) {
                if (!in_array($url, $discoveredUrls)) {
                    $discoveredUrls[] = $url;
                }
            }
        }

        if ($constrainToAccountPlan) {
            $accountPlan = $this->jobUserAccountPlanEnforcementService->getUserAccountPlanService()->getForUser($crawlJobContainer->getCrawlJob()->getUser())->getPlan();
            if ($accountPlan->hasConstraintNamed('urls_per_job')) {
                return array_slice($discoveredUrls, 0, $accountPlan->getConstraintNamed('urls_per_job')->getLimit());
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
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return CrawlJobContainer
     */
    private function create(Job $job) {        
        $crawlJob = new Job();
        $crawlJob->setType($this->jobTypeService->getCrawlType());
        $crawlJob->setState($this->jobService->getStartingState());
        $crawlJob->setUser($job->getUser());
        $crawlJob->setWebsite($job->getWebsite());
        $crawlJob->setParameters($job->getParameters());
        
        $this->getEntityManager()->persist($crawlJob); 
        
        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setParentJob($job);
        $crawlJobContainer->setCrawlJob($crawlJob);

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
    
    
    public function getAllActiveForUser(User $user) {
        return $this->getEntityRepository()->getAllForUserByCrawlJobStates($user, $this->jobService->getIncompleteStates());
    }
}