<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class PrepareCommandTest extends BaseSimplyTestableTestCase {    
    
    const EXPECTED_TASK_TYPE_COUNT = 3;
    
    public function testPrepareInWrongStateReturnsStatusCode1() {        
        $canonicalUrl = 'http://example.com';        
        $jobId = $this->createJobAndGetId($canonicalUrl);

        $this->assertInternalType('integer', $jobId);
        $this->assertGreaterThan(0, $jobId);
        
        $cancelResponse = $this->getJobController('cancelAction')->cancelAction($canonicalUrl, $jobId);
        $this->assertEquals(200, $cancelResponse->getStatusCode());
        
        $postCancelStatus = json_decode($this->getJobStatus($canonicalUrl, $jobId)->getContent())->state;
        $this->assertEquals('cancelled', $postCancelStatus);         
        
        $this->assertEquals(1, $this->runConsole('simplytestable:job:prepare', array(
            $jobId =>  true
        )));       
    }
    
    
    public function testPrepareInMaintenanceReadOnlyModeReturnsStatusCode2() {     
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));        
        $this->assertEquals(2, $this->runConsole('simplytestable:job:prepare', array(
            1 =>  true
        )));
    }    

    public function testPrepareNewJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        
        $expectedTaskUrls = array(
            'http://example.com/',
            'http://example.com/articles/',
            'http://example.com/articles/i-make-the-internet/'
        );        
        
        $jobCreateResponse = $this->createJob($canonicalUrl);        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertInternalType('integer', $job_id);
        $this->assertGreaterThan(0, $job_id);
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        )));
        
        $this->getJobService()->getEntityRepository()->clear();
        
        $jobObject = $this->fetchJob($canonicalUrl, $job_id);
        $response = json_decode($jobObject->getContent());
        
        $this->assertEquals('queued', $response->state);
        $this->assertNotNull($response->time_period);
        $this->assertNotNull($response->time_period->start_date_time);
        
        $taskTypeCount = count($response->task_types);
        
        $this->assertEquals(count($expectedTaskUrls), $response->url_count);
        $this->assertEquals(count($expectedTaskUrls) * $taskTypeCount, $response->task_count);
        $this->assertEquals(count($expectedTaskUrls) * $taskTypeCount, $response->task_count_by_state->queued);
        
        $job = $this->getJobService()->getById($job_id);        
        $discoveredTaskUrls = array();
        
        foreach ($job->getTasks() as $task) {
            if (!in_array($task->getUrl(), $discoveredTaskUrls)) {
                $discoveredTaskUrls[] = $task->getUrl();
            } 
        } 
        
        foreach ($discoveredTaskUrls as $taskUrl) {
            $this->assertTrue(in_array($taskUrl, $expectedTaskUrls));
        }
        
        foreach ($expectedTaskUrls as $taskUrl) {
            $this->assertTrue(in_array($taskUrl, $discoveredTaskUrls));
        }        
        
        return;
    }
    
    
    public function testSingleUrlJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        
        $expectedTaskUrls = array(
            'http://example.com/'
        );        
        
        $jobCreateResponse = $this->createJob($canonicalUrl, null, 'single url');        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertInternalType('integer', $job_id);
        $this->assertGreaterThan(0, $job_id);       
        
        $this->assertEquals(1, $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        )));
        
        $this->getJobService()->getEntityRepository()->clear();
        
        $job = $this->fetchJob($canonicalUrl, $job_id);
        $response = json_decode($job->getContent());
        
        $this->assertEquals('queued', $response->state);
        $this->assertEquals('Single URL', $response->type);
        $this->assertNotNull($response->time_period);
        $this->assertNotNull($response->time_period->start_date_time);
        
        $taskTypeCount = count($response->task_types);
        
        $this->assertEquals(count($expectedTaskUrls), $response->url_count);
        $this->assertEquals($taskTypeCount, $response->task_count);
        $this->assertEquals($taskTypeCount, $response->task_count_by_state->queued);
        
        return;
    }
    
    
    public function testPrepareFullSiteTestWithPublicUserCreatesUrlCountAmmendment() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';        
        $jobCreateResponse = $this->createJob($canonicalUrl);        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertInternalType('integer', $job_id);
        $this->assertGreaterThan(0, $job_id);
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        )));
        
        $this->getJobService()->getEntityRepository()->clear();
        
        $job = $this->getJobService()->getById($job_id);
        $this->assertEquals(1, $job->getAmmendments()->count());
        $this->assertEquals('plan-url-limit-reached:discovered-url-count-11', $job->getAmmendments()->first()->getReason());
    }
    
    
    public function testPrepareFullSiteJobWithNoSitemapSetsJobStateAsFailedNoSitemap() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';        
        $job_id = $this->createJobAndGetId($canonicalUrl);
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        ));
        
        $job = $this->getJobService()->getById($job_id);
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState()->getName(), $job->getState()->getName());
    }
    
    
    public function testPrepareFullSiteJobOwnedByPublicUserWithNoSitemapDoesNotAutostartCrawlJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';        
        $job_id = $this->createJobAndGetId($canonicalUrl);
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        ));
        
        $job = $this->getJobService()->getById($job_id);
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState()->getName(), $job->getState()->getName());
    }    
    
    public function testPrepareFullSiteJobOwnedByNonPublicUserWithNoSitemapDoesAutostartCrawlJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');
        
        $canonicalUrl = 'http://example.com/';        
        $job_id = $this->createJobAndGetId($canonicalUrl, $user->getEmail());
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        ));
        
        $job = $this->getJobService()->getById($job_id);
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState()->getName(), $job->getState()->getName());
        
        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->assertEquals($job_id, $crawlJobContainer->getParentJob()->getId());
        
        $this->assertEquals($this->getJobService()->getQueuedState(), $crawlJobContainer->getCrawlJob()->getState());
    }
    
    
    public function testHandleSitemapContainingHostlessUrls() {
        $canonicalUrl = 'http://example.com/';
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));
        
        $this->assertEquals(0, count($job->getTasks()));
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $job->getState());
    }
    
    public function testHandleSingleIndexLargeSitemap() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';        

        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));
        
        $jobControllerResponse = json_decode($this->getJobController('statusAction')->statusAction($job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals(10, $jobControllerResponse->url_count);
    } 
    
    
    public function testHandleLargeCollectionOfSitemaps() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $canonicalUrl = 'http://example.com/';        

        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));
        
        $jobControllerResponse = json_decode($this->getJobController('statusAction')->statusAction($job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals(10, $jobControllerResponse->url_count);
    }

}
