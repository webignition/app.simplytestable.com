<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class LatestTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }
    
    public function testLatestActionForPublicUser() {        
        $canonicalUrl = 'http://example.com';        
        $jobId = $this->createJobAndGetId($canonicalUrl);        
        
        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);
        
        $this->assertEquals(302, $response->getStatusCode());        
        $this->assertEquals($jobId, $this->getJobIdFromUrl($response->getTargetUrl()));
    }
    
    
    public function testLatestActionForDifferentUsers() {
        $canonicalUrl1 = 'http://one.example.com/';
        $canonicalUrl2 = 'http://two.example.com/';  
        
        $user1 = $this->createAndActivateUser('user1@example.com', 'password1');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password1');
                
        $jobId1 = $this->createJobAndGetId($canonicalUrl1, $user1->getEmail());
        $jobId2 = $this->createJobAndGetId($canonicalUrl2, $user2->getEmail());
        $jobId3 = $this->createJobAndGetId($canonicalUrl1);
        
        $response1 = $this->getJobController('latestAction', array(
            'user' => $user1->getEmail()
        ))->latestAction($canonicalUrl1);
        
        $response2 = $this->getJobController('latestAction', array(
            'user' => $user2->getEmail()
        ))->latestAction($canonicalUrl2);
        
        $response3 = $this->getJobController('latestAction')->latestAction($canonicalUrl1);        
        
        $this->assertEquals(302, $response1->getStatusCode()); 
        $this->assertEquals(302, $response2->getStatusCode()); 
        $this->assertEquals(302, $response3->getStatusCode()); 
        
        $this->assertEquals($jobId1, $this->getJobIdFromUrl($response1->getTargetUrl()));
        $this->assertEquals($jobId2, $this->getJobIdFromUrl($response2->getTargetUrl()));
        $this->assertEquals($jobId3, $this->getJobIdFromUrl($response3->getTargetUrl()));       
    }
    
    
    public function testLatestActionReturns404ForNoLatestJob() {        
        $canonicalUrl = 'http://example.com';            
        
        $response = $this->getJobController('latestAction')->latestAction($canonicalUrl);        
        $this->assertEquals(404, $response->getStatusCode());          
    }
    
}


