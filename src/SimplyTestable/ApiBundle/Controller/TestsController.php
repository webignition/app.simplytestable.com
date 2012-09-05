<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\State;

class TestsController extends ApiController
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
        
        return $this->sendResponse($job);
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
        
        return $this->sendResponse($job);
    }

    
    public function summaryAction($site_root_url, $test_id)
    { 
        $this->siteRootUrl = $site_root_url;
        $this->testId = $test_id;
        
        $job = $this->getJob();
        if ($job === false) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;  
        }
        
        $jobData = array(
            'id' => $job->getId(),
            'user' => $job->getPublicSerializedUser(),
            'website' => $job->getPublicSerializedWebsite(),
            'state' => $job->getPublicSerializedState(),
            'url_total' => $job->getUrlTotal(),
            'task_count_by_state' => $this->getTaskCountByState($job),
            'task_types' => $job->getRequestedTaskTypes()
        );
        
        return $this->sendResponse($jobData);
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
    
    public function resultsAction($site_root_url, $test_id)
    {
        return new \Symfony\Component\HttpFoundation\Response(json_encode(array(
            'site_root_url' => $site_root_url,
            'test_id' => $test_id
        )));
    }
    
    
    public function taskStatusAction($site_root_url, $test_id, $task_id) {
        $this->siteRootUrl = $site_root_url;
        $this->testId = $test_id;
        
        $job = $this->getJob();
        if ($job === false) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;  
        }
        
        $task = $this->getTaskService()->getById($task_id);
        if (is_null($task) || !$job->getTasks()->contains($task)) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;              
        }
        
        return $this->sendResponse($task);
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
        
        $this->getTaskService()->getCountByJobAndState($job, $this->getTaskService()->getCompletedState());
        
        if (is_null($job)) {
            return false;           
        }
        
        $job->setUrlTotal($this->container->get('simplytestable.services.taskservice')->getUrlCountByJob($job));
        return $job;      
    }   
    
    
    /**
     *
     * @param Job $job
     * @return array 
     */
    private function getTaskCountByState(Job $job) {
        $taskCountByState = array();
        $taskCountByState['awaiting-cancellation'] = $this->getTaskService()->getCountByJobAndState($job, $this->getTaskService()->getAwaitingCancellationState());
        $taskCountByState['cancelled'] = $this->getTaskService()->getCountByJobAndState($job, $this->getTaskService()->getCancelledState());
        $taskCountByState['completed'] = $this->getTaskService()->getCountByJobAndState($job, $this->getTaskService()->getCompletedState());
        $taskCountByState['in-progress'] = $this->getTaskService()->getCountByJobAndState($job, $this->getTaskService()->getInProgressState());
        $taskCountByState['queued'] = $this->getTaskService()->getCountByJobAndState($job, $this->getTaskService()->getQueuedState());
        $taskCountByState['queued-for-assignment'] = $this->getTaskService()->getCountByJobAndState($job, $this->getTaskService()->getQueuedForAssignmentState());        
        
        return $taskCountByState;
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
}
