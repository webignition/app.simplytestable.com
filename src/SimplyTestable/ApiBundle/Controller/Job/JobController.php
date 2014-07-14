<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

class JobController extends BaseJobController
{
    protected $testId = null;    
    
    public function latestAction($site_root_url) {
        $website = $this->get('simplytestable.services.websiteservice')->fetch($site_root_url);        
        $latestJob = null;

        if ($this->getTeamService()->hasTeam($this->getUser()) || $this->getTeamService()->getMemberService()->belongsToTeam($this->getUser())) {
            $team = $this->getTeamService()->getForUser($this->getUser());

            $latestJob = $this->getJobService()->getEntityRepository()->findLatestByWebsiteAndUsers(
                $website,
                $this->getTeamService()->getPeople($team)
            );

            if (!is_null($latestJob)) {
                return $this->redirect($this->generateUrl('job_job_status', array(
                    'site_root_url' => $latestJob->getWebsite()->getCanonicalUrl(),
                    'test_id' => $latestJob->getId()
                ), true));
            }
        }

        if (!$this->getUserService()->isPublicUser($this->getUser())) {
            $latestJob = $this->getJobService()->getEntityRepository()->findLatestByWebsiteAndUsers(
                $website,
                array(
                    $this->getUser()
                )
            );

            if (!is_null($latestJob)) {
                return $this->redirect($this->generateUrl('job_job_status', array(
                    'site_root_url' => $latestJob->getWebsite()->getCanonicalUrl(),
                    'test_id' => $latestJob->getId()
                ), true));
            }
        }

        $latestJob = $this->getJobService()->getEntityRepository()->findLatestByWebsiteAndUsers(
            $website,
            array(
                $this->getUserService()->getPublicUser()
            )
        );

        
        if (is_null($latestJob)) {
            $response = new Response();
            $response->setStatusCode(404);
            return $response;              
        }
        
        return $this->redirect($this->generateUrl('job_job_status', array(
            'site_root_url' => $latestJob->getWebsite()->getCanonicalUrl(),
            'test_id' => $latestJob->getId()
        ), true));
    }
    
    public function setPublicAction($site_root_url, $test_id) {
        return $this->setIsPublic($site_root_url, $test_id, true);
    }
    
    public function setPrivateAction($site_root_url, $test_id) {
        return $this->setIsPublic($site_root_url, $test_id, false);
    }
    
    public function isPublicAction($site_root_url, $test_id) {
        $response = new Response();
        
        if (!$this->getJobService()->getIsPublic($test_id)) {
            $response->setStatusCode(404);
        }
 
        return $response;
    }
    
    private function setIsPublic($site_root_url, $test_id, $isPublic) {
        if ($this->getUserService()->isPublicUser($this->getUser())) {
            return $this->redirect($this->generateUrl('job_job_status', array(
                'site_root_url' => $site_root_url,
                'test_id' => $test_id
            ), true));
        }

        $this->getJobRetrievalService()->setUser($this->getUser());

        try {
            $job = $this->getJobRetrievalService()->retrieve($test_id);
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }

        if ($job->getIsPublic() !== $isPublic) {
            $job->setIsPublic(filter_var($isPublic, FILTER_VALIDATE_BOOLEAN));
            $this->getJobService()->persistAndFlush($job);
        }
        
        return $this->redirect($this->generateUrl('job_job_status', array(
            'site_root_url' => $site_root_url,
            'test_id' => $job->getId()
        ), true));         
    }
    
    
    public function statusAction($site_root_url, $test_id) {
        $this->getJobRetrievalService()->setUser($this->getUser());

        try {
            return $this->sendResponse($this->getSummary(
                $this->populateJob($this->getJobRetrievalService()->retrieve($test_id))
            ));
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }
    }

    
    public function cancelAction($site_root_url, $test_id)
    {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }   
        
        if ($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $this->getJobRetrievalService()->setUser($this->getUser());

        try {
            $job = $this->getJobRetrievalService()->retrieve($test_id);
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }
        
        $this->testId = $test_id;

        if ($job->getState()->equals($this->getJobService()->getFailedNoSitemapState())) {
            $crawlJob = $this->getCrawlJobContainerService()->getForJob($job)->getCrawlJob();            
            $this->cancelAction($site_root_url, $crawlJob->getId());
        }
        
        if ($job->getType()->equals($this->getJobTypeService()->getCrawlType())) {
            $parentJob = $this->getCrawlJobContainerService()->getForJob($job)->getParentJob();            
            
            foreach ($parentJob->getRequestedTaskTypes() as $taskType) {
                /* @var $taskType TaskType */
                $taskTypeParameterDomainsToIgnoreKey = strtolower(str_replace(' ', '-', $taskType->getName())) . '-domains-to-ignore';            

                if ($this->container->hasParameter($taskTypeParameterDomainsToIgnoreKey)) {
                    $this->getJobPreparationService()->setPredefinedDomainsToIgnore($taskType, $this->container->getParameter($taskTypeParameterDomainsToIgnoreKey));
                }
            }            
            
            $this->getJobPreparationService()->prepareFromCrawl($this->getCrawlJobContainerService()->getForJob($parentJob));          
            
            if ($this->getResqueQueueService()->isEmpty('task-assignment-selection')) {
                $this->getResqueQueueService()->add(
                    'SimplyTestable\ApiBundle\Resque\Job\TaskAssignmentSelectionJob',
                    'task-assignment-selection'
                );             
            }             
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
        $this->testId = $test_id;
        
        $job = $this->getJobByVisibilityOrUser();
        if ($job === false) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;  
        }
        
        $taskIds = $this->getRequestTaskIds();        
        $tasks = $this->getTaskService()->getEntityRepository()->getCollectionByJobAndId($job, $taskIds);
        
        foreach ($tasks as $task) {
            /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */            
            if (!$this->getTaskService()->isFinished($task)) {
                $task->setOutput(null);
            }                       
        }
        
        return $this->sendResponse($tasks);
    }
    
    
    public function taskIdsAction($site_root_url, $test_id) {                
        $this->testId = $test_id;
        
        $job = $this->getJobByVisibilityOrUser();
        if ($job === false) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;  
        }        
     
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($job);
        
        return $this->sendResponse($taskIds);
    }    
    
    
    public function listUrlsAction($site_root_url, $test_id) {      
        $this->testId = $test_id;
        
        $job = $this->getJobByVisibilityOrUser();
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
    protected function getJobByUser() {
        $job = $this->getJobService()->getEntityRepository()->findOneBy(array(
            'id' => $this->testId,
            'user' => array(
                $this->getUser(),
                $this->getUserService()->getPublicUser()
            )
        ));

        if (is_null($job)) {
            return false;
        }

        return $this->populateJob($job);
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job 
     */
    protected function getJobByVisibilityOrUser() {
        // Check for jobs that are public by owner
        $publicJob = $this->getJobService()->getEntityRepository()->findOneBy(array(
            'id' => $this->testId,
            'isPublic' => true            
        ));
        
        if (!is_null($publicJob)) {
            return $this->populateJob($publicJob);
        }
        
        return $this->getJobByUser();
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
     * @return \SimplyTestable\ApiBundle\Services\JobPreparationService
     */
    private function getJobPreparationService() {
        return $this->container->get('simplytestable.services.jobpreparationservice');
    } 
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\ResqueQueueService
     */        
    private function getResqueQueueService() {
        return $this->get('simplytestable.services.resqueQueueService');
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\Team\Service
     */
    private function getTeamService() {
        return $this->get('simplytestable.services.teamservice');
    }
}
