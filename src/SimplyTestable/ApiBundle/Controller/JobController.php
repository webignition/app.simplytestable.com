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
    private $siteRootUrl = null;
    private $testId = null;    
    
    public function startAction($site_root_url)
    {        
        $this->siteRootUrl = $site_root_url;
        
        $existingJobs = $this->getJobService()->getEntityRepository()->getAllByWebsiteAndStateAndUser(
            $this->getWebsite(),
            $this->getJobService()->getIncompleteStates(),
            $this->getUser()
        );

        $existingJobId = null;
        
        $requestedTaskTypes = $this->getTaskTypes();        
        foreach ($existingJobs as $existingJob) {
            if ($this->jobMatchesRequestedTaskTypes($existingJob, $requestedTaskTypes)) {
                $existingJobId = $existingJob->getId();
            }
        }
        
        if (is_null($existingJobId)) {            
            $job = $this->getJobService()->create(
                $this->getUser(),
                $this->getWebsite(),
                $this->getTaskTypes()
            );
            
            $this->get('simplytestable.services.resqueQueueService')->add(
                'SimplyTestable\ApiBundle\Resque\Job\JobPrepareJob',
                'job-prepare',
                array(
                    'id' => $job->getId()
                )                
            );
            
            $existingJobId = $job->getId();
        } else {
            $job = $this->getJobService()->getById($existingJobId);
        }
        
        return $this->redirect($this->generateUrl('job', array(
            'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            'test_id' => $job->getId()
        )));
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @param array $requestedTaskTypes
     * @return boolean
     */
    private function jobMatchesRequestedTaskTypes(Job $job, $requestedTaskTypes) {            
        $jobTaskTypes = $job->getRequestedTaskTypes();
        
        foreach ($requestedTaskTypes as $requestedTaskType) {
            if (!$jobTaskTypes->contains($requestedTaskType)) {
                return false;
            }
        }
           
        $jobTaskTypeArray = $jobTaskTypes->toArray();
        foreach ($jobTaskTypeArray as $jobTaskType) {
            if (!in_array($jobTaskType, $requestedTaskTypes)) {
                return false;
            }
        }

        return true;     
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
        return array(
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
            'skipped_task_count' => $this->getJobService()->getSkippedTaskCount($job)
        );        
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


        foreach ($job->getTasks() as $task) {
            $tasksToDeAssign[] = array(
                'id' => $task->getId()
            );
        }      
        
        $this->get('simplytestable.services.resqueQueueService')->removeCollection(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            $tasksToDeAssign
        );

        $tasksAwaitingCancellation = $this->getTaskService()->getAwaitingCancellationByJob($job);
        $taskIds = array();
        
        foreach($tasksAwaitingCancellation as $task) {
            $taskIds[] = $task->getId();
        }
        
        if (count($taskIds) > 0) {
            $this->get('simplytestable.services.resqueQueueService')->add(
                'SimplyTestable\ApiBundle\Resque\Job\TaskCancelCollectionJob',
                'task-cancel',
                array(
                    'ids' => implode(',', $taskIds)
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
    private function getJob() {      
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
    private function getTaskTypes() {        
        $requestTaskTypes = $this->getRequestTaskTypes();                
        return (count($requestTaskTypes) === 0) ? $this->getAllSelectableTaskTypes() : $requestTaskTypes;
    }
    
    
    /**
     * 
     * @return array
     */
    private function getRequestTaskTypes() {                
        $requestTaskTypes = array();
        
        $requestedTaskTypes = $this->getRequestValue('test-types');
        
        if (!is_array($requestedTaskTypes)) {
            return $requestTaskTypes;
        }
        
        foreach ($requestedTaskTypes as $taskTypeName) {            
            if ($this->getTaskTypeService()->exists($taskTypeName)) {
                $taskType = $this->getTaskTypeService()->getByName($taskTypeName);                
                
                if ($taskType->isSelectable()) {
                    $requestTaskTypes[] = $taskType;
                }
            }
        }
        
        return $requestTaskTypes;
    }
    
    
    /**
     *
     * @return array
     */
    private function getAllSelectableTaskTypes() {
        return $this->getDoctrine()->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Type')->findBy(array(
            'selectable' => true
        ));
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
    private function getJobService() {
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
     * @return \SimplyTestable\ApiBundle\Services\TaskTypeService 
     */
    private function getTaskTypeService() {
        return $this->get('simplytestable.services.tasktypeservice');
    }    
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserService 
     */
    private function getUserService() {
        return $this->get('simplytestable.services.userservice');
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
    
    private function getRequestValue($key, $httpMethod = null) {
        $availableHttpMethods = array(
            HTTP_METH_GET,
            HTTP_METH_POST
        );
        
        $defaultHttpMethod = HTTP_METH_GET;
        $requestedHttpMethods = array();
        
        if (is_null($httpMethod)) {
            $requestedHttpMethods = $availableHttpMethods;
        } else {
            if (in_array($httpMethod, $availableHttpMethods)) {
                $requestedHttpMethods[] = $httpMethod;
            } else {
                $requestedHttpMethods[] = $defaultHttpMethod;
            }
        }
        
        foreach ($requestedHttpMethods as $requestedHttpMethod) {
            $requestValues = $this->getRequestValues($requestedHttpMethod);
            if ($requestValues->has($key)) {
                return $requestValues->get($key);
            }
        }
        
        return null;       
    }
    
    
    /**
     *
     * @param int $httpMethod
     * @return type 
     */
    private function getRequestValues($httpMethod = HTTP_METH_GET) {
        return ($httpMethod == HTTP_METH_POST) ? $this->container->get('request')->request : $this->container->get('request')->query;
    }    
}
