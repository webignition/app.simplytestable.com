<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class StatusTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }      
   
    public function testStatusAction() {
        $this->removeAllJobs();
        
        $canonicalUrl = 'http://example.com/';
        
        $jobId = $this->createJobAndGetId($canonicalUrl);
        
        $response = $this->getJobController('statusAction')->statusAction($canonicalUrl, $jobId);
        $responseJsonObject = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
        
        $this->assertEquals($jobId, $responseJsonObject->id);
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
        $this->removeAllJobs();

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
    
    
    public function testStatusForRejectedDueToPlanFullSiteConstraint() {
        $canonicalUrl = 'http://example.com/';
        
        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        
        $fullSiteJobsPerSiteConstraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');        
        $fullSiteJobsPerSiteLimit = $fullSiteJobsPerSiteConstraint->getLimit();
        
        for ($i = 0; $i < $fullSiteJobsPerSiteLimit; $i++) {
            $this->cancelJob($canonicalUrl, $this->createJobAndGetId($canonicalUrl));            
        }
        
        $rejectedJobId = $this->createJobAndGetId($canonicalUrl);        
        $jobStatusObject = json_decode($this->getJobController('statusAction')->statusAction($canonicalUrl, $rejectedJobId)->getContent());
        
        $this->assertNotNull($jobStatusObject->rejection);
        $this->assertEquals('plan-constraint-limit-reached', $jobStatusObject->rejection->reason);
        
        $this->assertNotNull($jobStatusObject->rejection->constraint);
        $this->assertNotNull($jobStatusObject->rejection->constraint->name);
        $this->assertEquals('full_site_jobs_per_site', $jobStatusObject->rejection->constraint->name);
                
    }
    
    
    public function testStatusForRejectedDueToPlanSingleUrlConstraint() {
        $canonicalUrl = 'http://example.com/';
        
        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        
        $fullSiteJobsPerSiteConstraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');        
        $fullSiteJobsPerSiteLimit = $fullSiteJobsPerSiteConstraint->getLimit();
        
        for ($i = 0; $i < $fullSiteJobsPerSiteLimit; $i++) {
            $this->cancelJob($canonicalUrl, $this->createJobAndGetId($canonicalUrl, null, 'single url'));            
        }
        
        $rejectedJobId = $this->createJobAndGetId($canonicalUrl, null, 'single url');        
        $jobStatusObject = json_decode($this->getJobController('statusAction')->statusAction($canonicalUrl, $rejectedJobId)->getContent());
        
        $this->assertNotNull($jobStatusObject->rejection);
        $this->assertEquals('plan-constraint-limit-reached', $jobStatusObject->rejection->reason);
        
        $this->assertNotNull($jobStatusObject->rejection->constraint);
        $this->assertNotNull($jobStatusObject->rejection->constraint->name);
        $this->assertEquals('single_url_jobs_per_url', $jobStatusObject->rejection->constraint->name);
                
    }    
    
}


