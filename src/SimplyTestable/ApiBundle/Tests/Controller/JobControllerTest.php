<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class JobControllerTest extends BaseControllerJsonTestCase {
   
    public function testStatusAction() {        
        $this->resetSystemState();
        
        $canonicalUrl = 'http://example.com/';
        
        $this->createJob($canonicalUrl);
        
        $response = $this->getJobController('statusAction')->statusAction($canonicalUrl, 1);
        $responseJsonObject = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
        
        $this->assertEquals(1, $responseJsonObject->id);
        $this->assertEquals('public', $responseJsonObject->user);
        $this->assertEquals($canonicalUrl, $responseJsonObject->website);        
        $this->assertEquals('new', $responseJsonObject->state);
        $this->assertEquals(0, $responseJsonObject->url_count);
        $this->assertEquals(0, $responseJsonObject->task_count);
        $this->assertEquals('Full site', $responseJsonObject->type);
        
        foreach ($responseJsonObject->task_count_by_state as $stateName => $taskCount) {
            $this->assertEquals(0, $taskCount);
        }
        
        $this->assertEquals(0, $responseJsonObject->errored_task_count);
        $this->assertEquals(0, $responseJsonObject->cancelled_task_count);
        $this->assertEquals(0, $responseJsonObject->skipped_task_count);
    }
    
    public function testStatusActionForDifferentUsers() {
        $this->setupDatabase();
        $canonicalUrl1 = 'http://one.example.com/';
        $canonicalUrl2 = 'http://two.example.com/';
        $canonicalUrl3 = 'http://three.example.com/';
        
        $user1 = $this->createAndActivateUser('user1@example.com', 'password1');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password1');
                
        $jobId1 = $this->createJobAndGetId($canonicalUrl1, $user1->getEmail());
        $jobId2 = $this->createJobAndGetId($canonicalUrl2, $user2->getEmail());
        $jobId3 = $this->createJobAndGetId($canonicalUrl3);
                
        $status1Response = $this->getJobStatus($canonicalUrl1, $jobId1, $user1->getEmail());
        $status1ResponseObject = json_decode($status1Response->getContent());        
        $this->assertEquals(200, $status1Response->getStatusCode());
        $this->assertEquals($user1->getEmail(), $status1ResponseObject->user);
        $this->assertEquals($canonicalUrl1, $status1ResponseObject->website);        
        
        $status2Response = $this->getJobStatus($canonicalUrl1, $jobId1, $user2->getEmail());        
        $this->assertEquals(403, $status2Response->getStatusCode());
        
        $status3Response = $this->getJobStatus($canonicalUrl1, $jobId1);
        $this->assertEquals(403, $status3Response->getStatusCode());
        
        $status4Response = $this->getJobStatus($canonicalUrl2, $jobId2, $user1->getEmail());
        $this->assertEquals(403, $status4Response->getStatusCode());
        
        $status5Response = $this->getJobStatus($canonicalUrl2, $jobId2, $user2->getEmail());
        $status5ResponseObject = json_decode($status5Response->getContent());        
        $this->assertEquals(200, $status5Response->getStatusCode());
        $this->assertEquals($user2->getEmail(), $status5ResponseObject->user);
        $this->assertEquals($canonicalUrl2, $status5ResponseObject->website); 
        
        $status6Response = $this->getJobStatus($canonicalUrl2, $jobId2);      
        $this->assertEquals(403, $status6Response->getStatusCode());
        
        $status7Response = $this->getJobStatus($canonicalUrl3, $jobId3, $user1->getEmail());
        $status7ResponseObject = json_decode($status7Response->getContent());        
        $this->assertEquals(200, $status7Response->getStatusCode());
        $this->assertEquals('public', $status7ResponseObject->user);
        $this->assertEquals($canonicalUrl3, $status7ResponseObject->website);          
        
        $status8Response = $this->getJobStatus($canonicalUrl3, $jobId3, $user2->getEmail());
        $status8ResponseObject = json_decode($status8Response->getContent());        
        $this->assertEquals(200, $status8Response->getStatusCode());
        $this->assertEquals('public', $status8ResponseObject->user);
        $this->assertEquals($canonicalUrl3, $status8ResponseObject->website); 
        
        $status9Response = $this->getJobStatus($canonicalUrl3, $jobId3);
        $status9ResponseObject = json_decode($status9Response->getContent());        
        $this->assertEquals(200, $status9Response->getStatusCode());
        $this->assertEquals('public', $status9ResponseObject->user);
        $this->assertEquals($canonicalUrl3, $status9ResponseObject->website);         
    } 
    
    
    public function testTaskIdsAction() {
        $this->setupDatabase();
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $job = $this->prepareJob($canonicalUrl, $job_id);
        
        $response = $this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id);
        $taskIds = json_decode($response->getContent());
        $expectedTaskIdCount = $job->url_count * count($job->task_types);
        
        $this->assertEquals($expectedTaskIdCount, count($taskIds));
        $this->assertEquals(array(1,2,3,4,5,6,7,8,9), $taskIds);      
    }
    
    
    public function testCancelAction() {
        $this->resetSystemState();
        
        $canonicalUrl = 'http://example.com';        
        $jobId = $this->createJobAndGetId($canonicalUrl);
        
        $preCancelStatus = json_decode($this->getJobStatus($canonicalUrl, $jobId)->getContent())->state;
        $this->assertEquals('new', $preCancelStatus);
        
        $cancelResponse = $this->getJobController('cancelAction')->cancelAction($canonicalUrl, $jobId);
        $this->assertEquals(200, $cancelResponse->getStatusCode());
        
        $postCancelStatus = json_decode($this->getJobStatus($canonicalUrl, $jobId)->getContent())->state;
        $this->assertEquals('cancelled', $postCancelStatus);        
    }
    
    
    public function testCancelActionInMaintenanceReadOnlyModeReturns503() {        
        $this->resetSystemState();        
        
        $canonicalUrl = 'http://example.com';
        $jobId = $this->createJobAndGetId($canonicalUrl);        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));   
        $this->assertEquals(503, $this->getJobController('cancelAction')->cancelAction($canonicalUrl, $jobId)->getStatusCode());        
    }
    
}


