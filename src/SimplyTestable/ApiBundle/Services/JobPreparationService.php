<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use webignition\NormalisedUrl\NormalisedUrl;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason as JobRejectionReason;

class JobPreparationService {
    
    const RETRUN_CODE_OK = 0;
    const RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;    
    const RETURN_CODE_NO_URLS = 3;
    const RETURN_CODE_UNROUTABLE = 4;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\JobService
     */
    private $jobService;    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\TaskService
     */
    private $taskService;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\JobTypeService
     */
    private $jobTypeService;    
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\WebSiteService
     */
    private $websiteService;
    
    
    /**
     *
     * @var array
     */
    private $processedUrls = array();
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService
     */
    private $jobUserAccountPlanEnforcementService;       
    
    
    /**
     *
     * @var array
     */
    private $predefinedDomainsToIgnore = array();
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\CrawlJobContainerService
     */
    private $crawlJobContainerService;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\UserService
     */
    private $userService;    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\ResqueQueueService
     */
    private $resqueService;      
    
    
    public function __construct(
        \SimplyTestable\ApiBundle\Services\JobService $jobService,
        \SimplyTestable\ApiBundle\Services\TaskService $taskService,
        \SimplyTestable\ApiBundle\Services\JobTypeService $jobTypeService,
        \SimplyTestable\ApiBundle\Services\WebSiteService $websiteService,
        \SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        \SimplyTestable\ApiBundle\Services\CrawlJobContainerService $crawlJobContainerService,
        \SimplyTestable\ApiBundle\Services\UserService $userService,
        \SimplyTestable\ApiBundle\Services\ResqueQueueService $resqueQueueService
    ) {
        $this->jobService = $jobService;
        $this->taskService = $taskService;
        $this->jobTypeService = $jobTypeService;
        $this->websiteService = $websiteService;
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->crawlJobContainerService = $crawlJobContainerService;
        $this->userService = $userService;
        $this->resqueService = $resqueQueueService;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Task\Type\Type $taskType
     * @param array $domainsToIgnore
     */
    public function setPredefinedDomainsToIgnore(TaskType $taskType, $domainsToIgnore)  {
        $this->predefinedDomainsToIgnore[$taskType->getName()] = $domainsToIgnore;
    }   

    
    
    public function prepare(Job $job) {        
        if (!$this->jobService->isNew($job)) {
            return self::RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE;
        }  

        $job->setState($this->jobService->getPreparingState());         
        $this->jobService->persistAndFlush($job);
        
        if (!$job->getWebsite()->isPubliclyRoutable()) {
            $this->rejectAsUnroutable($job);
            return self::RETURN_CODE_UNROUTABLE;
        }
        
        $this->processedUrls = array();        
        
        $this->jobUserAccountPlanEnforcementService->setUser($job->getUser());        
        $this->jobUserAccountPlanEnforcementService->getJobUrlLimitConstraint()->getLimit();      

        $urls = $this->collectUrlsForJob($job, $this->jobUserAccountPlanEnforcementService->getJobUrlLimitConstraint()->getLimit());
        
        if ($urls === false || count($urls) == 0) {            
            $job->setState($this->jobService->getFailedNoSitemapState());
            
            if (!$this->userService->isPublicUser($job->getUser())) {
                if (!$this->crawlJobContainerService->hasForJob($job)) {
                    $crawlJobContainer = $this->crawlJobContainerService->getForJob($job);
                    $this->crawlJobContainerService->prepare($crawlJobContainer);                                                
                    
                    if ($this->resqueService->isEmpty('task-assignment-selection')) {
                        $this->resqueService->add(
                            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignmentSelectionJob',
                            'task-assignment-selection'
                        );             
                    }                     
                }
            }            
            
            $this->jobService->persistAndFlush($job);
            return self::RETURN_CODE_NO_URLS;
        }
        
        if ($this->jobUserAccountPlanEnforcementService->isJobUrlLimitReached(count($urls))) {
            $this->jobService->addAmmendment($job, 'plan-url-limit-reached:discovered-url-count-' . count($urls), $this->jobUserAccountPlanEnforcementService->getJobUrlLimitConstraint());            
            $urls = array_slice($urls, 0, $this->jobUserAccountPlanEnforcementService->getJobUrlLimitConstraint()->getLimit());
        }
        
        $requestedTaskTypes = $job->getRequestedTaskTypes();
        $newTaskState = $this->taskService->getQueuedState();
        
        foreach ($urls as $url) {
            $comparatorUrl = new NormalisedUrl($url);
            if (!$this->isProcessedUrl($comparatorUrl)) {
                foreach ($requestedTaskTypes as $taskType) {
                    $taskTypeOptions = $this->getTaskTypeOptions($job, $taskType);

                    $task = new Task();
                    $task->setJob($job);
                    $task->setType($taskType);
                    $task->setUrl($url);
                    $task->setState($newTaskState);
                    
                    $job->addTask($task);
                    
                    $parameters = array();
                    
                    if ($taskTypeOptions->getOptionCount()) {
                        $parameters = $taskTypeOptions->getOptions();                      
                        
                        $domainsToIgnore = $this->getDomainsToIgnore($taskTypeOptions, $this->predefinedDomainsToIgnore);                                               
                        if (count($domainsToIgnore)) {
                            $parameters['domains-to-ignore'] = $domainsToIgnore;
                        }
                    }
                    
                    if ($job->hasParameters()) {
                        $parameters = array_merge($parameters, json_decode($job->getParameters(), true));
                    }
                    
                    $task->setParameters(json_encode($parameters));                    
                    
                    $this->taskService->persist($task);                    
                }
                
                $this->processedUrls[] = (string)$comparatorUrl;
            }
        }
        
        $job->setState($this->jobService->getQueuedState());
        
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $job->setTimePeriod($timePeriod);   
        
        $this->jobService->persistAndFlush($job);
        
        return self::RETRUN_CODE_OK;
    }
    
    private function rejectAsUnroutable(Job $job) {
        $this->jobService->reject($job);

        $rejectionReason = new JobRejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('unroutable');
        
        $this->jobService->getEntityManager()->persist($rejectionReason);
        $this->jobService->getEntityManager()->flush();       
        $this->jobService->persistAndFlush($job);
    }
    
    
    public function prepareFromCrawl(CrawlJobContainer $crawlJobContainer) {
        $this->processedUrls = array();
        $job = $crawlJobContainer->getParentJob();
        
        if (!$this->jobService->isFailedNoSitepmap($job)) {
            return self::RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE;
        }  
        
        $job->setState($this->jobService->getPreparingState());         
        $this->jobService->persistAndFlush($job);
        
        $urls = $this->crawlJobContainerService->getDiscoveredUrls($crawlJobContainer, true);
        
        if ($urls === false || count($urls) == 0) {
            $job->setState($this->jobService->getFailedNoSitemapState());
            $this->jobService->persistAndFlush($job);
            return self::RETURN_CODE_NO_URLS;
        }
        
        if ($crawlJobContainer->getCrawlJob()->getAmmendments()->count()) {
            /* @var $ammendment \SimplyTestable\ApiBundle\Entity\Job\Ammendment */
            
            foreach ($crawlJobContainer->getCrawlJob()->getAmmendments() as $ammendment) {
                /* @var $ammendment \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint */
                $constraint = $ammendment->getConstraint();                
                
                if ($constraint->getName() == JobUserAccountPlanEnforcementService::URLS_PER_JOB_CONSTRAINT_NAME) {                    
                    $this->jobService->addAmmendment($job, $ammendment->getReason(), $constraint);                             
                }
            }          
        }
        
        $this->jobUserAccountPlanEnforcementService->setUser($job->getUser());        
        if ($this->jobUserAccountPlanEnforcementService->isJobUrlLimitReached(count($urls))) {
            $this->jobService->addAmmendment($job, 'plan-url-limit-reached:discovered-url-count-' . count($urls), $this->jobUserAccountPlanEnforcementService->getJobUrlLimitConstraint());            
            $urls = array_slice($urls, 0, $this->jobUserAccountPlanEnforcementService->getJobUrlLimitConstraint()->getLimit());
        }
        
        $requestedTaskTypes = $job->getRequestedTaskTypes();
        $newTaskState = $this->taskService->getQueuedState();
        
        foreach ($urls as $url) {
            $comparatorUrl = new NormalisedUrl($url);
            if (!$this->isProcessedUrl($comparatorUrl)) {                
                foreach ($requestedTaskTypes as $taskType) {
                    $taskTypeOptions = $this->getTaskTypeOptions($job, $taskType);

                    $task = new Task();
                    $task->setJob($job);
                    $task->setType($taskType);
                    $task->setUrl($url);
                    $task->setState($newTaskState);
                    
                    $parameters = array();
                    
                    if ($taskTypeOptions->getOptionCount()) {
                        $parameters = $taskTypeOptions->getOptions();                      
                        
                        $domainsToIgnore = $this->getDomainsToIgnore($taskTypeOptions, $this->predefinedDomainsToIgnore);                                               
                        if (count($domainsToIgnore)) {
                            $parameters['domains-to-ignore'] = $domainsToIgnore;
                        }
                    }
                    
                    if ($job->hasParameters()) {
                        $parameters = array_merge($parameters, json_decode($job->getParameters(), true));
                    }
                    
                    $task->setParameters(json_encode($parameters));
                    
                    $this->taskService->persist($task);
                    $job->addTask($task);
                }
                
                $this->processedUrls[] = (string)$comparatorUrl;
            }
        }
        
        $job->setState($this->jobService->getQueuedState());
        
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $job->setTimePeriod($timePeriod);   
        
        $this->jobService->persistAndFlush($job);
        
        return self::RETRUN_CODE_OK;
    }
    
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return boolean
     */
    private function isSingleUrlJob(Job $job) {
        return $job->getType()->equals($this->jobTypeService->getSingleUrlType());
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @param int $softLimit
     * @return array
     */
    private function collectUrlsForJob(Job $job, $softLimit) {
        $parameters = ($job->hasParameters()) ? json_decode($job->getParameters(), true) : array();
        $parameters['softLimit'] = $softLimit;
        
        if ($this->isSingleUrlJob($job)) {        
            return array($job->getWebsite()->getCanonicalUrl());
        } else { 
            try {
                return $this->websiteService->getUrls($job->getWebsite(), $parameters);         
            } catch (\Exception $e) {
                var_dump($e);
                exit();
            }
        }        
    }
    
    /**
     * 
     * @param \webignition\NormalisedUrl\NormalisedUrl $url
     * @return boolean
     */
    private function isProcessedUrl(NormalisedUrl $url) {
        return in_array((string)$url, $this->processedUrls);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @param \SimplyTestable\ApiBundle\Entity\Task\Type\Type $taskType
     * @return \SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions
     */
    private function getTaskTypeOptions(Job $job, TaskType $taskType) {
        foreach ($job->getTaskTypeOptions() as $taskTypeOptions) {
            /* @var $taskTypeOptions TaskTypeOptions */
            if ($taskTypeOptions->getTaskType()->equals($taskType)) {
                return $taskTypeOptions;
            }
        }
        
        return new TaskTypeOptions();
    }   
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions $taskTypeOptions
     * @param array $predefinedDomainsToIgnore
     * @return array
     */
    private function getDomainsToIgnore(TaskTypeOptions $taskTypeOptions, $predefinedDomainsToIgnore) {
        $rawDomainsToIgnore = array();
        
        if ($this->shouldIgnoreCommonCdns($taskTypeOptions)) {
            if (isset($predefinedDomainsToIgnore[$taskTypeOptions->getTaskType()->getName()])) {
                $rawDomainsToIgnore = array_merge($rawDomainsToIgnore, $predefinedDomainsToIgnore[$taskTypeOptions->getTaskType()->getName()]);
            }
        }
        
        if ($this->hasDomainsToIgnore($taskTypeOptions)) {
            $specifiedDomainsToIgnore = $taskTypeOptions->getOption('domains-to-ignore');
            if (is_array($specifiedDomainsToIgnore)) {
                $rawDomainsToIgnore = array_merge($rawDomainsToIgnore, $specifiedDomainsToIgnore);
            }
        }
        
        $domainsToIgnore = array();
        foreach ($rawDomainsToIgnore as $domainToIgnore) {
            $domainToIgnore = trim(strtolower($domainToIgnore));
            if (!in_array($domainToIgnore, $domainsToIgnore)) {
                $domainsToIgnore[] = $domainToIgnore;
            }
        }
        
        return $domainsToIgnore;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions $taskTypeOptions
     * @return boolean
     */
    private function shouldIgnoreCommonCdns(TaskTypeOptions $taskTypeOptions) {
        return $taskTypeOptions->getOption('ignore-common-cdns') == '1';
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions $taskTypeOptions
     * @return boolean
     */
    private function hasDomainsToIgnore(TaskTypeOptions $taskTypeOptions) {
        return is_array($taskTypeOptions->getOption('domains-to-ignore'));
    } 
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\WebSiteService
     */
    public function getWebsiteService() {
        return $this->websiteService;
    }
}