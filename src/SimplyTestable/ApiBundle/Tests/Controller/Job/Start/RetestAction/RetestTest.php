<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start\RetestAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class RetestTest extends BaseControllerJsonTestCase {
    
    public function testWithInvalidId() {        
        $this->assertEquals(400, $this->getJobStartController('retestAction')->retestAction('foo', 1)->getStatusCode());
    }    
    
    public function testWithIncompleteJob() {  
        $jobId = $this->createJobAndGetId('http://example.com/');
        $this->assertEquals(400, $this->getJobStartController('retestAction')->retestAction('foo', $jobId)->getStatusCode());
    }       
    
    public function testRetestJobIsNotOriginalJob() {  
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));
        $job->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($job);        
        
        $response = $this->getJobStartController('retestAction')->retestAction('foo', $job->getId());
        
        $retestJobId = $this->getJobIdFromUrl($response->getTargetUrl());
        $retestJob = $this->getJobService()->getById($retestJobId);
        
        $this->assertNotEquals($job->getId(), $retestJob->getId());
    }    
    
    public function testWebsiteIsCloned() {  
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));
        $job->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($job);        
        
        $response = $this->getJobStartController('retestAction')->retestAction('foo', $job->getId());
        
        $retestJobId = $this->getJobIdFromUrl($response->getTargetUrl());
        $retestJob = $this->getJobService()->getById($retestJobId);
        
        $this->assertEquals($job->getWebsite()->getId(), $retestJob->getWebsite()->getId());
    }    
    
    public function testTypeIsClonedForFullSiteJob() {  
        $job = $this->getJobService()->getById($this->createJobAndGetId('http://example.com/'));
        $job->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($job);        
        
        $response = $this->getJobStartController('retestAction')->retestAction('foo', $job->getId());
        
        $retestJobId = $this->getJobIdFromUrl($response->getTargetUrl());
        $retestJob = $this->getJobService()->getById($retestJobId);
        
        $this->assertEquals($job->getType()->getName(), $retestJob->getType()->getName());
    }    
    
    public function testTypeIsClonedForSingleUrlJob() {  
        $job = $this->getJobService()->getById($this->createJobAndGetId(
            'http://example.com/',
            null,
            'single url'
        ));
        $job->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($job);        
        
        $response = $this->getJobStartController('retestAction')->retestAction('foo', $job->getId());
        
        $retestJobId = $this->getJobIdFromUrl($response->getTargetUrl());
        $retestJob = $this->getJobService()->getById($retestJobId);
        
        $this->assertEquals($job->getType()->getName(), $retestJob->getType()->getName());
    }  

    public function testTaskTypesAreCloned() {  
        $job = $this->getJobService()->getById($this->createJobAndGetId(
            'http://example.com/',
            null,
            null,
            array(
                'html validation',
                'css validation'
            )
        ));
        $job->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($job);        
        
        $response = $this->getJobStartController('retestAction')->retestAction('foo', $job->getId());
        
        $retestJobId = $this->getJobIdFromUrl($response->getTargetUrl());
        $retestJob = $this->getJobService()->getById($retestJobId);
        
        $jobTaskTypeNames = array();
        foreach ($job->getRequestedTaskTypes() as $taskType) {
            $jobTaskTypeNames[] = $taskType->getName();
        }
        
        $retestJobTaskTypeNames = array();
        foreach ($retestJob->getRequestedTaskTypes() as $taskType) {
            $retestJobTaskTypeNames[] = $taskType->getName();
        } 
        
        $this->assertEquals($jobTaskTypeNames, $retestJobTaskTypeNames);
    }     
    
    public function testTaskTypeOptionsAreCloned() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(
            'http://example.com/',
            null,
            null,
            array(
                'JS static analysis'
            ),
            array(
                'JS static analysis' => array(
                    'ignore-common-cdns' => 1,
                    'jslint-foo' => 1
                )
            )
        ));
        $job->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($job);        
        
        $response = $this->getJobStartController('retestAction')->retestAction('foo', $job->getId());
        
        $retestJobId = $this->getJobIdFromUrl($response->getTargetUrl());
        $retestJob = $this->getJobService()->getById($retestJobId);
        
        $jobTaskTypeOptionsArray = array();        
        foreach ($job->getTaskTypeOptions() as $taskTypeOptions) {
            $jobTaskTypeOptionsArray[strtolower($taskTypeOptions->getTaskType()->getName())] = $taskTypeOptions->getOptions();
        }        
        
        $retestJobTaskTypeOptionsArray = array();        
        foreach ($retestJob->getTaskTypeOptions() as $taskTypeOptions) {
            $retestJobTaskTypeOptionsArray[strtolower($taskTypeOptions->getTaskType()->getName())] = $taskTypeOptions->getOptions();
        }
        
        $this->assertEquals($jobTaskTypeOptionsArray, $retestJobTaskTypeOptionsArray);
    }     
   
   
}


