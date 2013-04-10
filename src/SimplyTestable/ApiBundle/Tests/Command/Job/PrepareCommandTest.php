<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class PrepareCommandTest extends BaseSimplyTestableTestCase {    
    
    public function setUp() {
        parent::setUp();
        self::setupDatabase();
    }
    
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
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
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
    
    
    public function testNoRobotsTxtNoSitemapXmlNoRssNoAtomGetsNoUrls() {
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
        
        $jobObject = $this->fetchJob($canonicalUrl, $job_id);
        $response = json_decode($jobObject->getContent());
        
        $this->assertEquals('no-sitemap', $response->state);
        
        $this->assertEquals(3, count($response->task_types));
        $this->assertEquals(0, $response->url_count);
        $this->assertEquals(0, $response->task_count);        
    }
    
    
    public function testNoRobotsTxtNoSitemapXmlNoRssHasAtomGetsAtomUrls() {
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
        
        $jobObject = $this->fetchJob($canonicalUrl, $job_id);
        $response = json_decode($jobObject->getContent());
        
        $this->assertEquals('queued', $response->state);
        
        $this->assertEquals(3, count($response->task_types));
        $this->assertEquals(1, $response->url_count);
        $this->assertEquals(3, $response->task_count);        
    }    

}
