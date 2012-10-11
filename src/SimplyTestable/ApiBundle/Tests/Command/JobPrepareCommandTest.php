<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Job;

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
        $this->assertEquals(count($expectedTaskUrls), $response->task_count_by_state->queued);
        
        return;
        
        var_dump($response);
        exit();
       
        
        $expectedUrls = array(
            'http://example.com/',
            'http://example.com/articles/',
            'http://example.com/articles/i-make-the-internet/'
        );
        
        $this->assertEquals('queued', $response->state);
        $this->assertEquals(3, count($response->tasks));
        
        foreach ($response->tasks as $taskIndex => $task) {
            $this->assertEquals($taskIndex + 1, $task->id);
            $this->assertEquals('queued', $task->state);
            $this->assertEquals($expectedUrls[$taskIndex], $task->url);
        }
    }
    
    

    
    



}
