<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class WorkerTaskAssignmentSelectionCommandTest extends BaseSimplyTestableTestCase {    

    public function testSelectTasksForAssignment() {        
        $taskTypeCount = $this->getTaskTypeService()->getSelectableCount();
        
        $this->setupDatabase();
        
        $canonicalUrl = 'http://example.com/';   
        
        $jobCreateResponse = $this->createJob($canonicalUrl);        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertEquals(1, $job_id);        
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        // Test with no workers (no tasks should be selected for assignment)
        $this->runConsole('simplytestable:task:assign:select');        
        
        $jobResponse = $this->fetchJob($canonicalUrl, $job_id);        
        $jobObject = json_decode($jobResponse->getContent());
        
        $this->assertEquals(200, $jobResponse->getStatusCode());
        $this->assertEquals(3 * $taskTypeCount, $jobObject->task_count_by_state->{'queued'});
        $this->assertEquals(0, $jobObject->task_count_by_state->{'queued-for-assignment'});
        
        // Test with one worker
        $this->createWorker();
        $this->runConsole('simplytestable:task:assign:select');
        
        $jobResponse = $this->fetchJob($canonicalUrl, $job_id);        
        $jobObject = json_decode($jobResponse->getContent());
        
        $this->assertEquals(200, $jobResponse->getStatusCode());
        $this->assertEquals((3 * $taskTypeCount) - 2, $jobObject->task_count_by_state->{'queued'});
        $this->assertEquals(2, $jobObject->task_count_by_state->{'queued-for-assignment'});
    }
    
    

    
    



}
