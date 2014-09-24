<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Controller\ApiController;

abstract class BaseJobController extends ApiController
{
    
    /**
     *
     * @param Job $job
     * @return array 
     */
    protected function getSummary(Job $job) {
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
            'warninged_task_count' => $this->getJobService()->getWarningedTaskCount($job),
            'task_type_options' => $this->getJobTaskTypeOptions($job),
            'type' => $job->getPublicSerializedType(),
            'is_public' => $this->getIsJobPublic($job),
            'parameters' => $job->getParameters(),
            'error_count' => $this->getErrorCount($job),
            'warning_count' => $this->getWarningCount($job),
            'owners' => $this->getSerializedOwners($job)
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
                'id' => $crawlJobContainer->getCrawlJob()->getId(),
                'state' => $crawlJobContainer->getCrawlJob()->getPublicSerializedState(),
                'processed_url_count' => count($this->getCrawlJobContainerService()->getProcessedUrls($crawlJobContainer)),
                'discovered_url_count' => count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)),                
            );
            
            $userAccountPlan = $this->getUserAccountPlanService()->getForUser($job->getUser())->getPlan();
            
            if ($userAccountPlan->hasConstraintNamed('urls_per_job')) {
                $jobSummary['crawl']['limit'] = $userAccountPlan->getConstraintNamed('urls_per_job')->getLimit();
            }
        }
        
        return $jobSummary;        
    }
    

    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    protected function populateJob(Job $job) {
        $this->getTaskService()->getCountByJobAndState($job, $this->getTaskService()->getCompletedState());        
        $job->setUrlCount($this->container->get('simplytestable.services.taskservice')->getUrlCountByJob($job));
        return $job;         
    }    
    

    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return int
     */    
    private function getErrorCount(Job $job) {
        return $this->getTaskService()->getEntityRepository()->getErrorCountByJob($job);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return int
     */
    private function getWarningCount(Job $job) {
        return $this->getTaskService()->getEntityRepository()->getWarningCountByJob($job);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return boolean
     */
    private function getIsJobPublic(Job $job) {
        if ($this->getUserService()->isPublicUser($this->getUser())) {
            return true;
        }
        
        return $job->getIsPublic();
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
     * @param Job $job
     * @return string[]
     */
    private function getSerializedOwners(Job $job) {
        $owners = $this->getOwners($job);
        $serializedOwners = [];

        foreach ($owners as $owner) {
            $serializedOwners[] = $owner->getUsername();
        }

        return $serializedOwners;
    }


    /**
     * @param Job $job
     * @return User[]
     */
    private function getOwners(Job $job) {
        if (!$this->getTeamService()->hasForUser($this->getUser())) {
            return [
                $job->getUser()
            ];
        }

        $team = $this->getTeamService()->getForUser($job->getUser());
        $members = $this->getTeamService()->getMemberService()->getMembers($team);

        $owners = [
            $team->getLeader()
        ];

        foreach ($members as $member) {
            if (!$this->userCollectionContainsUser($owners, $member->getUser())) {
                $owners[] = $member->getUser();
            }
        }

        return $owners;
    }


    /**
     * @param User[] $users
     * @param User $user
     * @return bool
     */
    private function userCollectionContainsUser(array $users, User $user) {
        foreach ($users as $currentUser) {
            if ($user->equals($currentUser)) {
                return true;
            }
        }

        return false;
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
    protected function getTaskService() {
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
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    private function getUserAccountPlanService() {
        return $this->get('simplytestable.services.UserAccountPlanService');
    }     
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\CrawlJobContainerService 
     */
    protected function getCrawlJobContainerService() {
        return $this->get('simplytestable.services.crawljobcontainerservice');
    }        
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobTypeService
     */
    protected function getJobTypeService() {
        return $this->get('simplytestable.services.JobTypeService');
    }  
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobPreparationService
     */
    private function getJobPreparationService() {
        return $this->container->get('simplytestable.services.jobpreparationservice');
    } 


    /**
     * @return \SimplyTestable\ApiBundle\Services\Job\RetrievalService
     */
    protected function getJobRetrievalService() {
        return $this->get('simplytestable.services.job.retrievalservice');
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\Team\Service
     */
    private function getTeamService() {
        return $this->container->get('simplytestable.services.teamservice');
    }
}
