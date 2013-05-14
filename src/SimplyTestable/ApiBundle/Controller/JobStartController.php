<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\State;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;

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
        $requestedJobType = $this->getRequestJobType();
        
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
                $requestedJobType
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
}
