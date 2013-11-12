<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\ListAction;

class CurrentTest extends AbstractListTest {  
    
    public function testForPublicUserWithNoLimitAndNoTests() {
        $list = json_decode($this->getJobController('listAction', array(), array(
            'exclude-finished' => '1'
        ))->listAction()->getContent());
        $this->assertEquals(array(), $list->jobs);  
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
        
        $list = json_decode($this->getJobController('listAction', array(), array(
            'exclude-finished' => '1'
        ))->listAction(count($canonicalUrls))->getContent());
        
        foreach (array_reverse($canonicalUrls) as $index => $canonicalUrl) {
            $this->assertEquals($canonicalUrl, $list->jobs[$index]->website);
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
        $list = json_decode($this->getJobController('listAction', array(), array(
            'exclude-finished' => '1'
        ))->listAction($limit)->getContent());
        
        $this->assertEquals($limit, count($list->jobs));  
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
        $list = json_decode($this->getJobController('listAction', array(), array(
            'exclude-finished' => '1'
        ))->listAction($limit)->getContent());
        
        $this->assertEquals($limit, count($list->jobs));  
    }     

    public function testForPublicUserWithNoLimitAndVariedIncompleteStateTests() {
        $incompleteStates = $this->getJobService()->getIncompleteStates();
        
        $jobs = array();
        foreach ($incompleteStates as $incompleteState) {
            $jobs[$incompleteState->getName()] = $this->getJobService()->getById($this->createJobAndGetId('http://'.$incompleteState->getName().'.example.com/'));
        }
        
        $list = json_decode($this->getJobController('listAction', array(), array(
            'exclude-finished' => '1'
        ))->listAction(count($incompleteStates))->getContent());
        
        $this->assertEquals(count($incompleteStates), count($list->jobs));
        
        foreach (array_reverse($incompleteStates) as $index => $incompleteState) {
            $this->assertEquals('http://'.$incompleteState->getName().'.example.com/', $list->jobs[$index]->website);
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
        
        $list = json_decode($this->getJobController('listAction', array(), array(
            'exclude-finished' => '1'
        ))->listAction(count($jobs))->getContent());
        
        $this->assertEquals(count($incompleteStates), count($list->jobs));
        
        foreach (array_reverse($incompleteStates) as $index => $incompleteState) {
            $this->assertEquals('http://'.$incompleteState->getName().'.example.com/', $list->jobs[$index]->website);
        }    
    }
    
    
    public function testIncludeFailedNoSitemapJobsThatHaveActiveCrawlJobs() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');
        $canonicalUrl = 'http://example.com';
        
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, $user->getEmail()));
        
        $list = json_decode($this->getJobController('listAction', array(
            'user' => $user->getEmail()
        ), array(
            'exclude-finished' => '1'
        ))->listAction()->getContent());
        
        $listContainsCrawlingParentJob = false;
        foreach ($list->jobs as $listedJob) {
            if ($listedJob->id == $job->getId()) {
                $listContainsCrawlingParentJob = true;
            }
        }
        
        $this->assertTrue($listContainsCrawlingParentJob); 
    }
    
    
    public function testListIsSortedByJobId() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');
        
        $jobNotCrawling = $this->getJobService()->getById($this->createAndPrepareJob('http://foo.example.com', $user->getEmail()));        
        $jobIsCrawling = $this->getJobService()->getById($this->createAndPrepareJob('http://example.com', $user->getEmail()));
        
        $list = json_decode($this->getJobController('listAction', array(
            'user' => $user->getEmail(),
            'exclude-finished' => '1'            
        ))->listAction(10)->getContent());
      
        $this->assertEquals($jobIsCrawling->getId(), $list->jobs[0]->id);
        $this->assertEquals($jobNotCrawling->getId(), $list->jobs[1]->id);
    }
    
    public function testDoesNotIncludeCrawlJobs() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');
        $canonicalUrl = 'http://example.com';
        
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, $user->getEmail()));
        
        $list = json_decode($this->getJobController('listAction', array(
            'user' => $user->getEmail(),
            'exclude-finished' => '1'  
        ))->listAction(10)->getContent());
        
        $this->assertEquals(1, count($list->jobs));
        $this->assertEquals($job->getId(), $list->jobs[0]->id);
    }
    
    
    public function testListIncludesJobUrlCount() {
        $this->createJob('http://one.example.com/');        
        $list = json_decode($this->getJobController('listAction')->listAction(10)->getContent());
        
        $this->assertTrue(isset($list->jobs[0]->url_count));   
    }     
    
}


