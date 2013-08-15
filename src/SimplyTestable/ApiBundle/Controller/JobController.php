<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\State;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;

class JobController extends ApiController
{
    protected $siteRootUrl = null;
    protected $testId = null;
    
    
    public function latestAction($site_root_url) {
        $this->siteRootUrl = $site_root_url;
        
        $latestJob = null;
        
        if (!$this->getUserService()->isPublicUser($this->getUser())) {
            $latestJob = $this->getJobService()->getEntityRepository()->findLatestByWebsiteAndUsers(
                $this->getWebsite(),
                array(
                    $this->getUser()
                )
            );            
        }
        
        if (is_null($latestJob)) {
            $latestJob = $this->getJobService()->getEntityRepository()->findLatestByWebsiteAndUsers(
                $this->getWebsite(),
                array(   
                    $this->getUserService()->getPublicUser()
                )
            );            
        }
        
        if (is_null($latestJob)) {
            $response = new Response();
            $response->setStatusCode(404);
            return $response;              
        }
        
        return $this->redirect($this->generateUrl('job', array(
            'site_root_url' => $latestJob->getWebsite()->getCanonicalUrl(),
            'test_id' => $latestJob->getId()
        ), true));
    }
    
    public function statusAction($site_root_url, $test_id)
    {
        $this->siteRootUrl = $site_root_url;
        $this->testId = $test_id;
        
        $job = $this->getJob();
        if ($job === false) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;  
        }
        
        return $this->sendResponse($this->getSummary($job));
    }
    
    
    /**
     *
     * @param Job $job
     * @return array 
     */
    private function getSummary(Job $job) {
        $jobSummary = array(
            'id' => $job->getId(),
            'user' => $job->getPublicSerializedUser(),
            'website' => $job->getPublicSerializedWebsite(),
            'state' => $job->getPublicSerializedState(),
            'time_period' => $job->getTimePeriod(),
            'url_count' => $job->getUrlCount(),
            'task_count' => $this->getTaskService()->getCountByJob($job),
            'task_count_by_state' => $this->getTaskCountByState($job),
            'task_types' => $job->getRequestedTaskTypes(),
            'errored_task_count' => $this->getJobService()->getErroredTaskCount($job),
            'cancelled_task_count' => $this->getJobService()->getCancelledTaskCount($job),
            'skipped_task_count' => $this->getJobService()->getSkippedTaskCount($job),
            'task_type_options' => $this->getJobTaskTypeOptions($job),
            'type' => $job->getPublicSerializedType()
        );
        
        if ($this->getJobService()->isRejected($job)) {            
            $jobSummary['rejection'] = $this->getJobRejectionReasonService()->getForJob($job);
        }
        
        if (!is_null($job->getAmmendments()) && $job->getAmmendments()->count() > 0) {
            $jobSummary['ammendments'] = $job->getAmmendments();
        }
        
        if ($this->getCrawlJobContainerService()->hasForJob($job)) {
            $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);            
            $jobSummary['crawl'] = array(
                'state' => $crawlJobContainer->getCrawlJob()->getPublicSerializedState(),
                'processed_url_count' => count($this->getCrawlJobContainerService()->getProcessedUrls($crawlJobContainer)),
                'discovered_url_count' => count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true))
            );
        }
        
        return $jobSummary;        
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return array
     */
    private function getJobTaskTypeOptions(Job $job) {
        $jobTaskTypeOptions = array();
        
        foreach ($job->getTaskTypeOptions() as $taskTypeOptions) {
            /* @var $taskTypeOptions \SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions */            
            $jobTaskTypeOptions[$taskTypeOptions->getTaskType()->getName()] = $taskTypeOptions->getOptions();
        }
        
        return $jobTaskTypeOptions;
    }
    
    
    public function listAction($limit = 1)
    {
        $limit = filter_var($limit, FILTER_VALIDATE_INT, array(
            'options' => array(
                'default' => 1,
                'min_range' => 0
            )
        ));
        
        $jobs = $this->getJobService()->getEntityRepository()->findAllByUserOrderedByIdDesc($this->getUser(), $limit);
        $jobSummaries = array();
        
        foreach ($jobs as $job) {
            $jobSummaries[] = $this->getSummary($job);
        }
        
        return $this->sendResponse($jobSummaries);       
    }    
    
    public function cancelAction($site_root_url, $test_id)
    {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }   
        
        if ($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }
        
        $this->siteRootUrl = $site_root_url;
        $this->testId = $test_id;
        
        $job = $this->getJob();
        if ($job === false) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;  
        }
        
        $preCancellationState = clone $job->getState();

        $this->getJobService()->cancel($job);        
        
        if ($preCancellationState->equals($this->getJobService()->getStartingState())) {            
            $this->get('simplytestable.services.resqueQueueService')->remove(
                'SimplyTestable\ApiBundle\Resque\Job\JobPrepareJob',
                'job-prepare',
                array(
                    'id' => $job->getId()
                )                
            );
        }
        
        $tasksToDeAssign = array();        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($job);     
        foreach ($taskIds as $taskId) {
            $tasksToDeAssign[] = array(
                'id' => $taskId
            );            
        }      
        
        $this->get('simplytestable.services.resqueQueueService')->removeCollection(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            $tasksToDeAssign
        );

        $tasksAwaitingCancellation = $this->getTaskService()->getAwaitingCancellationByJob($job);
        $taskIdsToCancel = array();

        foreach($tasksAwaitingCancellation as $task) {
            $taskIdsToCancel[] = $task->getId();
        }
        
        if (count($taskIdsToCancel) > 0) {
            $this->get('simplytestable.services.resqueQueueService')->add(
                'SimplyTestable\ApiBundle\Resque\Job\TaskCancelCollectionJob',
                'task-cancel',
                array(
                    'ids' => implode(',', $taskIdsToCancel)
                )              
            );               
        }    
        
        return $this->sendSuccessResponse();
    }    
    
    public function tasksAction($site_root_url, $test_id) {        
        $this->siteRootUrl = $site_root_url;
        $this->testId = $test_id;
        
        $job = $this->getJob();
        if ($job === false) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;  
        }
        
        $taskIds = $this->getRequestTaskIds();        
        $tasks = $this->getTaskService()->getEntityRepository()->getCollectionByJobAndId($job, $taskIds);
        
        return $this->sendResponse($tasks);
    }
    
    
    public function taskIdsAction($site_root_url, $test_id) {        
        $this->siteRootUrl = $site_root_url;
        $this->testId = $test_id;
        
        $job = $this->getJob();
        if ($job === false) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;  
        }        
     
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($job);
        
        return $this->sendResponse($taskIds);
    }    
    
    
    public function listUrlsAction($site_root_url, $test_id) {
        $this->siteRootUrl = $site_root_url;
        $this->testId = $test_id;
        
        $job = $this->getJob();
        if ($job === false) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;  
        }
        
        return $this->sendResponse($this->getTaskService()->getUrlsByJob($job));                
    }
    
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job 
     */
    protected function getJob() {      
        $job = $this->getJobService()->getEntityRepository()->findByIdAndWebsiteAndUsers(
            $this->testId,
            $this->getWebsite(),
            array(
                $this->getUser(),
                $this->getUserService()->getPublicUser()
            )
        );

        if (is_null($job)) {
            return false;           
        }
     
        $this->getTaskService()->getCountByJobAndState($job, $this->getTaskService()->getCompletedState());        
        $job->setUrlCount($this->container->get('simplytestable.services.taskservice')->getUrlCountByJob($job));
        return $job;      
    }
    
    
    /**
     *
     * @param Job $job
     * @return array 
     */
    private function getTaskCountByState(Job $job) {
        $availableStateNames = $this->getTaskService()->getAvailableStateNames();
        $taskCountByState = array();
        
        foreach ($availableStateNames as $stateName) {
            $stateShortName = str_replace('task-', '', $stateName);            
            $methodName = $this->stateNameToStateRetrievalMethodName($stateShortName);
            $taskCountByState[$stateShortName] = $this->getTaskService()->getCountByJobAndState($job, $this->getTaskService()->$methodName());         
        }
        
        return $taskCountByState;
    }
    
    
    /**
     *
     * @param string $stateName
     * @return string
     */
    private function stateNameToStateRetrievalMethodName($stateName) {
        $methodName = $stateName;
        
        $methodName = str_replace('-', ' ', $methodName);
        $methodName = ucwords($methodName);
        $methodName = str_replace(' ', '', $methodName);
        
        return 'get' . $methodName . 'State';
    }
    
    
    /**
     * 
     * @return array
     */
    private function getTaskTypeOptions() {
        $testTypeOptions = (is_array($this->getRequestValue('test-type-options'))) ? $this->getRequestValue('test-type-options') : array();
        
        foreach ($testTypeOptions as $taskTypeName => $options) {
            unset($testTypeOptions[$taskTypeName]);
            $testTypeOptions[strtolower($taskTypeName)] = $options;
        }
        
        return $testTypeOptions;
    }
    
    
    /**
     *
     * @return boolean
     */
    private function isTestEnvironment() {
        return $this->get('kernel')->getEnvironment() == 'test';
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\User 
     */
    public function getUser() {
        if (!$this->isTestEnvironment()) {                        
            return parent::getUser();
        }
        
        if  (is_null($this->getRequestValue('user'))) {
            return $this->get('simplytestable.services.userservice')->getPublicUser();
        }
        
        return $this->get('simplytestable.services.userservice')->findUserByEmail($this->getRequestValue('user'));
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\WebSite 
     */
    private function getWebsite() {        
        return $this->get('simplytestable.services.websiteservice')->fetch($this->siteRootUrl);
    }
    
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobService 
     */
    protected function getJobService() {
        return $this->get('simplytestable.services.jobservice');
    }
    
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\TaskService 
     */
    private function getTaskService() {
        return $this->get('simplytestable.services.taskservice');
    } 
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobRejectionReasonService 
     */
    private function getJobRejectionReasonService() {
        return $this->get('simplytestable.services.jobrejectionreasonservice');
    }    

    
    /**
     *
     * @return array|null
     */
    private function getRequestTaskIds() {        
        $requestTaskIds = $this->getRequestValue('taskIds');        
        $taskIds = array();
        
        if (substr_count($requestTaskIds, ':')) {
            $rangeLimits = explode(':', $requestTaskIds);
            
            for ($i = $rangeLimits[0]; $i<=$rangeLimits[1]; $i++) {
                $taskIds[] = $i;
            }
        } else {
            $rawRequestTaskIds = explode(',', $requestTaskIds);

            foreach ($rawRequestTaskIds as $requestTaskId) {
                if (ctype_digit($requestTaskId)) {
                    $taskIds[] = (int)$requestTaskId;
                }
            }            
        }
        
        return (count($taskIds) > 0) ? $taskIds : null;
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\CrawlJobContainerService 
     */
    protected function getCrawlJobContainerService() {
        return $this->get('simplytestable.services.crawljobcontainerservice');
    }        
    
    
}
