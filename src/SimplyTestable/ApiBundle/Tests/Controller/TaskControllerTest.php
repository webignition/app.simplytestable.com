<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class TaskControllerTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }       
   
    public function testCompleteAction() {        
        $this->removeAllJobs();
        $this->removeAllTasks();
        $this->createPublicUserIfMissing();
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        

        $result = $this->runConsole('simplytestable:task:assign', array(
            $taskIds[0] =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $job = json_decode($this->fetchJob($canonicalUrl, $job_id)->getContent());
        
        $this->assertEquals(0, $result);
        $this->assertEquals('in-progress', $job->state);
        
        $response = $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction('http://hydrogen.worker.simplytestable.com', 1);
        
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    
    public function testCompleteActionInMaintenanceReadOnlyModeReturns503() {                
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));
        
        $response = $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction('http://hydrogen.worker.simplytestable.com', 1);
        
        $this->assertEquals(503, $response->getStatusCode());       
    }
    
    public function testCompleteActionForNonExistentTaskReturns404() {
        $this->removeAllTasks();
        $this->createPublicUserIfMissing();
        
        $response = $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction('http://hydrogen.worker.simplytestable.com', 1);
        
        $this->assertEquals(404, $response->getStatusCode());
    }    
    
}


