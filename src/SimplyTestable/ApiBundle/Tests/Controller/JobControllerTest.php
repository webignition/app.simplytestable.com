<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class JobControllerTest extends BaseControllerJsonTestCase {

    public function testStartAction() {           
        $this->setupDatabase();
        
        $jobController = $this->getJobController('startAction');        
        
        $canonicalUrls = array(
            'http://one.example.com',
            'http://two.example.com',
            'http://three.example.com'
        );
        
        foreach ($canonicalUrls as $urlIndex => $canonicalUrl) {
            $response = $jobController->startAction($canonicalUrl);

            $this->assertEquals(302, $response->getStatusCode());        
            $this->assertEquals($urlIndex + 1, $this->getJobIdFromUrl($response->getTargetUrl()));            
        }       
        
        return;
    }
    
    public function testStatusAction() {        
        $this->setupDatabase();
        
        $canonicalUrl = 'http://example.com/';
        
        $jobController = $this->getJobController('statusAction');        
        $jobController->startAction($canonicalUrl);
        
        $response = $jobController->statusAction($canonicalUrl, 1);
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


