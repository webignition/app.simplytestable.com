<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

class StatusTest extends AbstractAccessTest {
    
    protected function getActionName() {
        return 'statusAction';
    }
    
    public function testStatusAction() {        
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
        $this->assertEquals(0, $responseJsonObject->warninged_task_count);
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
    
    
    public function testStatusForInstantlyPreparedSingleUrlJob() {
        $canonicalUrl = 'http://example.com/';
        
        $jobId = $this->createJobAndGetId($canonicalUrl, null, 'single url');        
        $jobObject = json_decode($this->getJobController('statusAction')->statusAction($canonicalUrl, $jobId)->getContent());
        
        $this->assertEquals(1, $jobObject->url_count);
        $this->assertEquals(4, $jobObject->task_count);      
    }
    
    
    public function testStatusForJobUrlLimitAmmendment() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $job_id = $this->createJobAndGetId($canonicalUrl);        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        )));        
        
        $jobObject = json_decode($this->getJobController('statusAction')->statusAction($canonicalUrl, $job_id)->getContent());
        
        $this->assertNotNull($jobObject->ammendments);
        $this->assertEquals(1, count($jobObject->ammendments));
        $this->assertEquals('plan-url-limit-reached:discovered-url-count-11', $jobObject->ammendments[0]->reason);
        $this->assertEquals('urls_per_job', $jobObject->ammendments[0]->constraint->name);      
    }
    
    public function testDefaultIsPublicIfOwnedByPublicUser() {        
        $canonicalUrl = 'http://example.com/';
        
        $jobId = $this->createJobAndGetId($canonicalUrl);
        
        $response = $this->getJobController('statusAction')->statusAction($canonicalUrl, $jobId);
        $responseJsonObject = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals('public', $responseJsonObject->user);
        $this->assertEquals(true, $responseJsonObject->is_public);
    }    
    
    public function testDefaultIsPrivateIfNotOwnedByPublicUser() {        
        $user = $this->createAndActivateUser('user@example.com', 'password1');
        
        $canonicalUrl = 'http://example.com/';
        
        $jobId = $this->createJobAndGetId($canonicalUrl, $user->getEmail());
        
        $response = $this->getJobStatus($canonicalUrl, $jobId, $user->getEmail());
        $responseJsonObject = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals($user->getEmail(), $responseJsonObject->user);
        $this->assertEquals(false, $responseJsonObject->is_public);
    }    
   
}


