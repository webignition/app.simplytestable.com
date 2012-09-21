<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Job\Job;
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
        
        return $this->redirect($this->generateUrl('job', array(
            'site_root_url' => (string)$job->getWebsite(),
            'test_id' => $job->getId()
        )));
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
            'url_count' => $job->getUrlCount(),
            'task_count' => $this->getTaskService()->getCountByJob($job),
            'task_count_by_state' => $this->getTaskCountByState($job),
            'task_types' => $job->getRequestedTaskTypes()
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
        
        $jobs = $this->getJobService()->getEntityRepository()->findAllOrderedByIdDesc($limit);
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
        $job = $this->getJobService()->getEntityRepository()->findOneBy(array(
            'id' => $this->testId,
            'user' => $this->getUser(),
            'website' => $this->getWebsite()
        ));

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
        return $this->getAllSelectableTaskTypes();
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
        
        return $this->get('simplytestable.services.userservice')->getPublicUser();
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
     * @return array|null
     */
    private function getRequestTaskIds($httpMethod = HTTP_METH_GET) {
        $requestValues = ($httpMethod == HTTP_METH_POST) ? $this->getRequest()->request : $this->getRequest()->query;        
        if (!$requestValues->has('taskIds')) {
            return null;
        }
        
        $requestTaskIds = array();
        
        if (substr_count($requestValues->get('taskIds'), ':')) {
            $rangeLimits = explode(':', $requestValues->get('taskIds'));
            
            for ($i = $rangeLimits[0]; $i<=$rangeLimits[1]; $i++) {
                $requestTaskIds[] = $i;
            }
        } else {
            $rawRequestTaskIds = explode(',', $requestValues->get('taskIds'));

            foreach ($rawRequestTaskIds as $requestTaskId) {
                if (ctype_digit($requestTaskId)) {
                    $requestTaskIds[] = (int)$requestTaskId;
                }
            }            
        }
        
        return (count($requestTaskIds) > 0) ? $requestTaskIds : null;
    }  
}
