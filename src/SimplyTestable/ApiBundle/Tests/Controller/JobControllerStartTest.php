<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class JobControllerStartTest extends BaseControllerJsonTestCase {

    public function testStartAction() {           
        $this->resetSystemState();
        
        $jobController = $this->getJobStartController('startAction');        
        
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
        $this->resetSystemState();
        $canonicalUrl = 'http://example.com/';
        
        $response1 = $this->createJob($canonicalUrl);
        $response2 = $this->createJob($canonicalUrl);
        $response3 = $this->createJob($canonicalUrl);
        
        $this->assertEquals('/job/http://example.com//1/', $response1->getTargetUrl());
        $this->assertEquals('/job/http://example.com//1/', $response2->getTargetUrl());
        $this->assertEquals('/job/http://example.com//1/', $response3->getTargetUrl());
    }
    
    
    public function testStartForExistingJobForDifferentUsers() {
        $this->resetSystemState();
        $canonicalUrl = 'http://example.com/';        
        $email1 = 'user1@example.com';
        $email2 = 'user2@example.com';
        
        $this->createAndActivateUser($email1, 'password1');
        $this->createAndActivateUser($email2, 'password1');
        
        $user1 = $this->getUserService()->findUserByEmail($email1);
        $user2 = $this->getUserService()->findUserByEmail($email2);
        
        $response1 = $this->createJob($canonicalUrl, $user1->getEmail());        
        $response2 = $this->createJob($canonicalUrl, $user2->getEmail());
        $response3 = $this->createJob($canonicalUrl, $user1->getEmail());        
        
        $this->assertEquals('/job/http://example.com//1/', $response1->getTargetUrl());
        $this->assertEquals('/job/http://example.com//2/', $response2->getTargetUrl());
        $this->assertEquals('/job/http://example.com//1/', $response3->getTargetUrl());        
    }
    
    
    public function testStartActionInMaintenanceReadOnlyModeReturns503() {           
        $this->resetSystemState();        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));
   
        $jobController = $this->getJobStartController('startAction');        
        $this->assertEquals(503, $jobController->startAction('http://example.com')->getStatusCode());
    }    
    
}


