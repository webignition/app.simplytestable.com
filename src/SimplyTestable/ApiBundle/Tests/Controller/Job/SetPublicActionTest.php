<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class SetPublicActionTest extends BaseControllerJsonTestCase {      
   
    public function testSetPublicByPublicUserForJobOwnedByPublicUser() {        
        $canonicalUrl = 'http://example.com/';
        
        $jobId = $this->createJobAndGetId($canonicalUrl);
        
        $setPublicResponse = $this->getJobController('setPublicAction')->setPublicAction($canonicalUrl, $jobId);
        $this->assertEquals(302, $setPublicResponse->getStatusCode());
      
        $statusResponse = $this->getJobController('statusAction')->statusAction($canonicalUrl, $jobId);
        $responseJsonObject = json_decode($statusResponse->getContent());

        $this->assertEquals('public', $responseJsonObject->user);
        $this->assertTrue($responseJsonObject->is_public);
    }
    
    public function testSetPrivateByPublicUserForJobOwnedByPublicUser() {        
        $canonicalUrl = 'http://example.com/';
        
        $jobId = $this->createJobAndGetId($canonicalUrl);
        
        $setPublicResponse = $this->getJobController('setPrivateAction')->setPrivateAction($canonicalUrl, $jobId);
        $this->assertEquals(302, $setPublicResponse->getStatusCode());
      
        $statusResponse = $this->getJobController('statusAction')->statusAction($canonicalUrl, $jobId);
        $responseJsonObject = json_decode($statusResponse->getContent());

        $this->assertEquals('public', $responseJsonObject->user);
        $this->assertTrue($responseJsonObject->is_public);
    }
    
    public function testSetPublicByNonPublicUserForJobOwnedBySameNonPublicUser() {        
        $canonicalUrl = 'http://example.com/';
        $user = $this->createAndActivateUser('user@example.com', 'password1');
        
        $jobId = $this->createJobAndGetId($canonicalUrl, $user->getEmail());       
        
        $setPublicResponse = $this->getJobController('setPublicAction', array(
            'user' => $user->getEmail()
        ))->setPublicAction($canonicalUrl, $jobId);
        $this->assertEquals(302, $setPublicResponse->getStatusCode());
      
        $statusResponse = $this->getJobController('statusAction', array(
            'user' => $user->getEmail()
        ))->statusAction($canonicalUrl, $jobId);
        $responseJsonObject = json_decode($statusResponse->getContent());

        $this->assertEquals($user->getEmail(), $responseJsonObject->user);
        $this->assertTrue($responseJsonObject->is_public);
    }    
    
    
    public function testSetPrivateByNonPublicUserForJobOwnedBySameNonPublicUser() {        
        $canonicalUrl = 'http://example.com/';
        $user = $this->createAndActivateUser('user@example.com', 'password1');
        
        $jobId = $this->createJobAndGetId($canonicalUrl, $user->getEmail());       
        
        $setPublicResponse = $this->getJobController('setPrivateAction', array(
            'user' => $user->getEmail()
        ))->setPrivateAction($canonicalUrl, $jobId);
        $this->assertEquals(302, $setPublicResponse->getStatusCode());
      
        $statusResponse = $this->getJobController('statusAction', array(
            'user' => $user->getEmail()
        ))->statusAction($canonicalUrl, $jobId);
        $responseJsonObject = json_decode($statusResponse->getContent());

        $this->assertEquals($user->getEmail(), $responseJsonObject->user);
        $this->assertFalse($responseJsonObject->is_public);
    }  
    
    
    public function testSetPublicByNonPublicUserForJobOwnedByPublicUser() {        
        $canonicalUrl = 'http://example.com/';
        $user = $this->createAndActivateUser('user@example.com', 'password1');
        
        $jobId = $this->createJobAndGetId($canonicalUrl);       
        
        $setPublicResponse = $this->getJobController('setPublicAction', array(
            'user' => $user->getEmail()
        ))->setPublicAction($canonicalUrl, $jobId);
        $this->assertEquals(302, $setPublicResponse->getStatusCode());
      
        $statusResponse = $this->getJobController('statusAction', array(
            'user' => $user->getEmail()
        ))->statusAction($canonicalUrl, $jobId);
        $responseJsonObject = json_decode($statusResponse->getContent());

        $this->assertEquals('public', $responseJsonObject->user);
        $this->assertTrue($responseJsonObject->is_public);
    }   
    
    
    public function testSetPrivateByNonPublicUserForJobOwnedByPublicUser() {        
        $canonicalUrl = 'http://example.com/';
        $user = $this->createAndActivateUser('user@example.com', 'password1');
        
        $jobId = $this->createJobAndGetId($canonicalUrl);       
        
        $setPublicResponse = $this->getJobController('setPublicAction', array(
            'user' => $user->getEmail()
        ))->setPublicAction($canonicalUrl, $jobId);
        $this->assertEquals(302, $setPublicResponse->getStatusCode());
      
        $statusResponse = $this->getJobController('statusAction', array(
            'user' => $user->getEmail()
        ))->statusAction($canonicalUrl, $jobId);
        $responseJsonObject = json_decode($statusResponse->getContent());

        $this->assertEquals('public', $responseJsonObject->user);
        $this->assertTrue($responseJsonObject->is_public);
    }   
    
    
    public function testSetPublicByNonPublicUserForJobOwnedByDifferentNonPublicUser() {        
        $canonicalUrl = 'http://example.com/';
        $user1 = $this->createAndActivateUser('user1@example.com', 'password1');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password1');
        
        $jobId = $this->createJobAndGetId($canonicalUrl, $user1->getEmail());       
        
        $setPublicResponse = $this->getJobController('setPublicAction', array(
            'user' => $user2->getEmail()
        ))->setPublicAction($canonicalUrl, $jobId);
        $this->assertEquals(403, $setPublicResponse->getStatusCode());
      
        $statusResponse = $this->getJobController('statusAction', array(
            'user' => $user1->getEmail()
        ))->statusAction($canonicalUrl, $jobId);
        $responseJsonObject = json_decode($statusResponse->getContent());

        $this->assertEquals($user1->getEmail(), $responseJsonObject->user);
        $this->assertFalse($responseJsonObject->is_public);
    }  
    
    public function testSetPrivateByNonPublicUserForJobOwnedByDifferentNonPublicUser() {        
        $canonicalUrl = 'http://example.com/';
        $user1 = $this->createAndActivateUser('user1@example.com', 'password1');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password1');
        
        $jobId = $this->createJobAndGetId($canonicalUrl, $user1->getEmail());       
        
        $setPublicResponse = $this->getJobController('setPrivateAction', array(
            'user' => $user2->getEmail()
        ))->setPrivateAction($canonicalUrl, $jobId);
        $this->assertEquals(403, $setPublicResponse->getStatusCode());
      
        $statusResponse = $this->getJobController('statusAction', array(
            'user' => $user1->getEmail()
        ))->statusAction($canonicalUrl, $jobId);
        $responseJsonObject = json_decode($statusResponse->getContent());

        $this->assertEquals($user1->getEmail(), $responseJsonObject->user);
        $this->assertFalse($responseJsonObject->is_public);
    }    
    
}


