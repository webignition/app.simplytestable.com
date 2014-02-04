<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason as JobRejectionReason;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;

class JobStartController extends ApiController
{
    private $siteRootUrl = null;   
    
    public function startAction($site_root_url)
    {        
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }        
        
        if ($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }
        
        $this->siteRootUrl = $site_root_url;
        
        if (!$this->getWebsite()->isPubliclyRoutable()) {
            return $this->rejectAsUnroutableAndRedirect();
        }

        $requestedJobType = $this->getRequestJobType();

        $this->getJobUserAccountPlanEnforcementService()->setUser($this->getUser());
        $this->getJobUserAccountPlanEnforcementService()->setJobType($requestedJobType);
        
        if ($requestedJobType->equals($this->getJobTypeService()->getFullSiteType())) {
            if ($this->getJobUserAccountPlanEnforcementService()->isFullSiteJobLimitReachedForWebSite($this->getWebsite())) {
                return $this->rejectAndRedirect($this->getJobUserAccountPlanEnforcementService()->getFullSiteJobLimitConstraint());
            }
        }
        
        
        if ($requestedJobType->equals($this->getJobTypeService()->getSingleUrlType())) { 
            if ($this->getJobUserAccountPlanEnforcementService()->isSingleUrlLimitReachedForWebsite($this->getWebsite())) {                
                return $this->rejectAndRedirect($this->getJobUserAccountPlanEnforcementService()->getSingleUrlJobLimitConstraint());
            }
        }        
        
        if ($this->getJobUserAccountPlanEnforcementService()->isUserCreditLimitReached()) {
            return $this->rejectAndRedirect($this->getJobUserAccountPlanEnforcementService()->getCreditsPerMonthConstraint());
        }
        
        $existingJobs = $this->getJobService()->getEntityRepository()->getAllByWebsiteAndStateAndUserAndType(
            $this->getWebsite(),
            $this->getJobService()->getIncompleteStates(),
            $this->getUser(),
            $requestedJobType
        );

        $existingJobId = null;
        
        if (count($existingJobs)) {
            $requestedTaskTypes = $this->getTaskTypes();        
            foreach ($existingJobs as $existingJob) {
                if ($this->jobMatchesRequestedTaskTypes($existingJob, $requestedTaskTypes)) {
                    $existingJobId = $existingJob->getId();
                }
            }            
        }
        
        if (is_null($existingJobId)) {            
            $job = $this->getJobService()->create(
                $this->getUser(),
                $this->getWebsite(),
                $this->getTaskTypes(),
                $this->getTaskTypeOptions(),
                $requestedJobType,
                $this->getParameters()
            );
            
            if ($this->getUserService()->isPublicUser($this->getUser())) {
                $job->setIsPublic(true);
                $this->getJobService()->persistAndFlush($job);
            }
            
            $this->getResqueQueueService()->add(
                'SimplyTestable\ApiBundle\Resque\Job\JobResolveJob',
                'job-prepare',
                array(
                    'id' => $job->getId()
                )                
            );
        } else {
            $job = $this->getJobService()->getById($existingJobId);
        }
        
        return $this->redirect($this->generateUrl('job', array(
            'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            'test_id' => $job->getId()
        )));
    }
    
    
    public function retestAction($site_root_url, $test_id) {
        $job = $this->getJobService()->getById($test_id);
        if (is_null($job)) {
            return $this->sendFailureResponse();
        }
        
        if (!$this->getJobService()->isFinished($job)) {
            return $this->sendFailureResponse();
        }
        
        $taskTypeNames = array();        
        foreach ($job->getRequestedTaskTypes() as $taskType) {
            $taskTypeNames[] = $taskType->getName();
        }
        
        $taskTypeOptionsArray = array();        
        foreach ($job->getTaskTypeOptions() as $taskTypeOptions) {
            $taskTypeOptionsArray[strtolower($taskTypeOptions->getTaskType()->getName())] = $taskTypeOptions->getOptions();
        }        
        
        /* @var $query \Symfony\Component\HttpFoundation\ParameterBag */
        $query = $this->get('request')->query;        
        $query->set('type', $job->getType()->getName());
        $query->set('test-types', $taskTypeNames);        
        $query->set('test-type-options', $taskTypeOptionsArray);
        
        return $this->startAction($job->getWebsite()->getCanonicalUrl());
    }      
    
    private function rejectAsUnroutableAndRedirect() {
        $job = $this->getJobService()->create(
            $this->getUser(),
            $this->getWebsite(),
            $this->getTaskTypes(),
            $this->getTaskTypeOptions(),
            $this->getRequestJobType(),
            $this->getParameters()
        );

        $this->getJobService()->reject($job);

        $rejectionReason = new JobRejectionReason();
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('unroutable');

        $this->getDoctrine()->getEntityManager()->persist($rejectionReason);
        $this->getDoctrine()->getEntityManager()->flush();

        return $this->redirect($this->generateUrl('job', array(
            'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            'test_id' => $job->getId()
        )));        
    }    
    
    private function rejectAndRedirect(AccountPlanConstraint $constraint) {
        $job = $this->getJobService()->create(
            $this->getUser(),
            $this->getWebsite(),
            $this->getTaskTypes(),
            $this->getTaskTypeOptions(),
            $this->getRequestJobType(),
            $this->getParameters()
        );

        $this->getJobService()->reject($job);

        $rejectionReason = new JobRejectionReason();
        $rejectionReason->setConstraint($constraint);
        $rejectionReason->setJob($job);
        $rejectionReason->setReason('plan-constraint-limit-reached');

        $this->getDoctrine()->getEntityManager()->persist($rejectionReason);
        $this->getDoctrine()->getEntityManager()->flush();

        return $this->redirect($this->generateUrl('job', array(
            'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            'test_id' => $job->getId()
        )));        
    }
    
    
    /**
     * 
     * @return JobType
     */
    private function getRequestJobType() {
        if (!$this->getJobTypeService()->has($this->getRequestValue('type'))) {
            return $this->getJobTypeService()->getDefaultType();
        }
        
        return $this->getJobTypeService()->getByName($this->getRequestValue('type'));       
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
    private function getParameters() {
        $featureOptions = (is_array($this->getRequestValue('parameters'))) ? $this->getRequestValue('parameters') : array();
        
        foreach ($featureOptions as $optionName => $optionValue) {
            unset($featureOptions[$optionName]);
            $featureOptions[urldecode(strtolower($optionName))] = $optionValue;
        }      
        
        return $featureOptions;        
    }
    
    
    /**
     * 
     * @return array
     */
    private function getTaskTypeOptions() {        
        $testTypeOptions = (is_array($this->getRequestValue('test-type-options'))) ? $this->getRequestValue('test-type-options') : array();
        
        foreach ($testTypeOptions as $taskTypeName => $options) {
            unset($testTypeOptions[$taskTypeName]);
            $testTypeOptions[urldecode(strtolower($taskTypeName))] = $options;
        }
        
        return $testTypeOptions;
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
            return $this->getUserService()->getPublicUser();
        }
        
        return $this->getUserService()->findUserByEmail($this->getRequestValue('user'));
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
     * @return \SimplyTestable\ApiBundle\Services\TaskTypeService 
     */
    private function getTaskTypeService() {
        return $this->get('simplytestable.services.tasktypeservice');
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobTypeService 
     */
    private function getJobTypeService() {
        return $this->get('simplytestable.services.jobtypeservice');
    } 
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService
     */
    private function getJobUserAccountPlanEnforcementService() {
        return $this->get('simplytestable.services.jobuseraccountplanenforcementservice');
    } 
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\ResqueQueueService
     */        
    private function getResqueQueueService() {
        return $this->get('simplytestable.services.resqueQueueService');
    }    
    

    
  
}
