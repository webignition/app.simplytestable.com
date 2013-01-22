<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class JobPrepareCommandTest extends BaseSimplyTestableTestCase {    

    public function testPrepareNewJob() {        
        $this->setupDatabase();
        
        $canonicalUrl = 'http://example.com/';
        
        $expectedTaskUrls = array(
            'http://example.com/',
            'http://example.com/articles/',
            'http://example.com/articles/i-make-the-internet/'
        );        
        
        $jobCreateResponse = $this->createJob($canonicalUrl);        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertEquals(1, $job_id);        
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->getJobService()->getEntityRepository()->clear();
        
        $job = $this->fetchJob($canonicalUrl, $job_id);
        $response = json_decode($job->getContent());
        
        $this->assertEquals('queued', $response->state);
        $this->assertNotNull($response->time_period);
        $this->assertNotNull($response->time_period->start_date_time);
        
        $taskTypeCount = count($response->task_types);
        
        $this->assertEquals(count($expectedTaskUrls), $response->url_count);
        $this->assertEquals(count($expectedTaskUrls) * $taskTypeCount, $response->task_count);
        $this->assertEquals(count($expectedTaskUrls) * $taskTypeCount, $response->task_count_by_state->queued);
        
        return;
    }
    
    
    public function testSingleUrlJob() {        
        $this->setupDatabase();
        
        $canonicalUrl = 'http://example.com/';
        
        $expectedTaskUrls = array(
            'http://example.com/'
        );        
        
        $jobCreateResponse = $this->createJob($canonicalUrl, null, 'full site');        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertEquals(1, $job_id);        
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->getJobService()->getEntityRepository()->clear();
        
        $job = $this->fetchJob($canonicalUrl, $job_id);
        $response = json_decode($job->getContent());
        
        $this->assertEquals('queued', $response->state);
        $this->assertEquals('Full site', $response->type);
        $this->assertNotNull($response->time_period);
        $this->assertNotNull($response->time_period->start_date_time);
        
        $taskTypeCount = count($response->task_types);
        
        $this->assertEquals(count($expectedTaskUrls), $response->url_count);
        $this->assertEquals($taskTypeCount, $response->task_count);
        $this->assertEquals($taskTypeCount, $response->task_count_by_state->queued);
        
        return;
    }
    
    



}
