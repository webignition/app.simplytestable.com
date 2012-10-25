<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class JobControllerTest extends BaseControllerJsonTestCase {
   
    public function testStatusAction() {        
        $this->setupDatabase();
        
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
        
        $user1 = $this->createAndActivateUser('user1@example.com');
        $user2 = $this->createAndActivateUser('user2@example.com');
                
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
    
    public function testListAction() {
        $this->setupDatabase();
        
        $jobController = $this->getJobController('listAction');        
        
        $canonicalUrls = array(
            'http://one.example.com/',
            'http://two.example.com/',
            'http://three.example.com/',
            'http://four.example.com/',
            'http://five.example.com/'
        );
        
        foreach ($canonicalUrls as $canonicalUrl) {
            $jobController->startAction($canonicalUrl);            
        }       
        
        // Test with no limit set (should default to a limit of 1)
        $listResponse = $jobController->listAction();
        
        $this->assertEquals(200, $listResponse->getStatusCode());
        
        $listResponseObject = json_decode($listResponse->getContent());
        
        $this->assertTrue(is_array($listResponseObject));
        $this->assertEquals(5, $listResponseObject[0]->id);
        $this->assertEquals('http://five.example.com/', $listResponseObject[0]->website);
        
        // Test with limits of 1 to 5 (number of jobs in the system)
        for ($limit = 1; $limit <= count($canonicalUrls); $limit++) {            
            $listResponse = $jobController->listAction($limit);
            $this->assertEquals(200, $listResponse->getStatusCode());
            $listResponseObject = json_decode($listResponse->getContent());
            
            $this->assertTrue(is_array($listResponseObject));
            $this->assertEquals($limit, count($listResponseObject));
            
            $expectedJobIds = $this->getExpectedJobIdsForLimit($limit, count($canonicalUrls));
            foreach ($expectedJobIds as $jobIdIndex => $expectedJobId) {
                $this->assertTrue($this->jobListContainsJobId($listResponseObject, $expectedJobId));
                $this->assertEquals($canonicalUrls[$expectedJobId - 1], $listResponseObject[$jobIdIndex]->website);
            }           
        }     
    }  

    /**
     * 
     * @param int $limit
     * @param int $jobCount
     * @return array
     */
    private function getExpectedJobIdsForLimit($limit, $jobCount) {
        $jobIds = array();
        
        while ($limit != count($jobIds)) {
            $jobIds[] = $jobCount - count($jobIds);
        }
        
        return $jobIds;
    }
    
    
    /**
     * 
     * @param array $jobList
     * @param int $id
     * @return boolean
     */
    private function jobListContainsJobId($jobList, $id) {        
        foreach ($jobList as $job) {
            if ($job->id == $id) {
                return true;
            }
        }
        
        return false;
    }     
    
}


