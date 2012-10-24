<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class JobControllerStartTest extends BaseControllerJsonTestCase {

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
    
    
    public function testStartForExistingJob() {
        $this->setupDatabase();
        $canonicalUrl = 'http://example.com/';
        
        $response1 = $this->createJob($canonicalUrl);
        $response2 = $this->createJob($canonicalUrl);
        $response3 = $this->createJob($canonicalUrl);
        
        $this->assertEquals('/job/http://example.com//1/', $response1->getTargetUrl());
        $this->assertEquals('/job/http://example.com//1/', $response2->getTargetUrl());
        $this->assertEquals('/job/http://example.com//1/', $response3->getTargetUrl());
    }
    
}


