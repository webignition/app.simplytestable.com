<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CurrentTest extends BaseControllerJsonTestCase {
    
    public function setUp() {        
        self::setupDatabase();
        parent::setUp();        
    }     
    
    public function testForPublicUserWithNoLimitAndNoTests() {
        $jobList = json_decode($this->getJobController('currentAction')->currentAction()->getContent());
        $this->assertEquals(array(), $jobList);  
    }    
    
    public function testForPublicUserWithNoLimitAndOnlyNewTests() {
        $canonicalUrls = array(
            'http://one.example.com/',
            'http://two.example.com/',
            'http://three.example.com/'
        );        
        
        foreach ($canonicalUrls as $canonicalUrl) {
            $this->createJob($canonicalUrl);
        }        
        
        $jobList = json_decode($this->getJobController('currentAction')->currentAction()->getContent());
        
        foreach (array_reverse($canonicalUrls) as $index => $canonicalUrl) {
            $this->assertEquals($canonicalUrl, $jobList[$index]->website);
        }    
    } 
    
    
    public function testForPublicUserWithLimitOneAndOnlyNewTests() {
        $canonicalUrls = array(
            'http://one.example.com/',
            'http://two.example.com/',
            'http://three.example.com/'
        );        
        
        foreach ($canonicalUrls as $canonicalUrl) {
            $this->createJob($canonicalUrl);
        }        
        
        $limit = 1;
        $jobList = json_decode($this->getJobController('currentAction')->currentAction($limit)->getContent());
        
        $this->assertEquals($limit, count($jobList));  
    }     
    
    
    public function testForPublicUserWithLimitTwoAndOnlyNewTests() {
        $canonicalUrls = array(
            'http://one.example.com/',
            'http://two.example.com/',
            'http://three.example.com/'
        );        
        
        foreach ($canonicalUrls as $canonicalUrl) {
            $this->createJob($canonicalUrl);
        }        
        
        $limit = 2;
        $jobList = json_decode($this->getJobController('currentAction')->currentAction($limit)->getContent());
        
        $this->assertEquals($limit, count($jobList));  
    }     

    public function testForPublicUserWithNoLimitAndVariedIncompleteStateTests() {
        $incompleteStates = $this->getJobService()->getIncompleteStates();
        
        $jobs = array();
        foreach ($incompleteStates as $incompleteState) {
            $jobs[$incompleteState->getName()] = $this->getJobService()->getById($this->createJobAndGetId('http://'.$incompleteState->getName().'.example.com/'));
        }
        
        $jobList = json_decode($this->getJobController('currentAction')->currentAction()->getContent());
        
        $this->assertEquals(count($incompleteStates), count($jobList));
        
        foreach (array_reverse($incompleteStates) as $index => $incompleteState) {
            $this->assertEquals('http://'.$incompleteState->getName().'.example.com/', $jobList[$index]->website);
        }    
    }    
    
    
    public function testForPublicUserWithIncompleteAndCompleteTests() {
        $incompleteStates = $this->getJobService()->getIncompleteStates();
        $finishedStates = $this->getJobService()->getFinishedStates();
        
        $jobs = array();
        foreach ($incompleteStates as $incompleteState) {
            $jobs[$incompleteState->getName()] = $this->getJobService()->getById($this->createJobAndGetId('http://'.$incompleteState->getName().'.example.com/'));
        }
        
        foreach ($finishedStates as $finishedState) {
            $job = $this->getJobService()->getById($this->createJobAndGetId('http://'.$finishedState->getName().'.example.com/'));
            $job->setState($finishedState);
            $this->getJobService()->persistAndFlush($job);
            
            $jobs[$finishedState->getName()] = $job;
        }
        
        $jobList = json_decode($this->getJobController('currentAction')->currentAction()->getContent());
        
        $this->assertEquals(count($incompleteStates), count($jobList));
        
        foreach (array_reverse($incompleteStates) as $index => $incompleteState) {
            $this->assertEquals('http://'.$incompleteState->getName().'.example.com/', $jobList[$index]->website);
        }    
    }
    
}


