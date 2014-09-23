<?php

namespace SimplyTestable\ApiBundle\Tests\Entity;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;

class UserEmailChangeRequestTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8NewEmail() {
        $newEmail = 'foo-ɸ@example.com';                
        $userEmailChangeRequest = new UserEmailChangeRequest();
        
        $userEmailChangeRequest->setUser($this->getUserService()->create('user@example.com', 'password'));
        $userEmailChangeRequest->setNewEmail($newEmail);
        $userEmailChangeRequest->setToken('foo-token');

        $this->getManager()->persist($userEmailChangeRequest);
        $this->getManager()->flush();
        
        $userEmailChangeRequestId = $userEmailChangeRequest->getId();
        
        $this->getManager()->clear();
  
        $this->assertEquals($newEmail, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest')->find($userEmailChangeRequestId)->getNewEmail());
    }
    
    
    public function testUtf8Token() {
        $token = 'foo-ɸ';       
           
        $userEmailChangeRequest = new UserEmailChangeRequest();
        
        $userEmailChangeRequest->setUser($this->getUserService()->create('user@example.com', 'password'));
        $userEmailChangeRequest->setNewEmail('user1@example.com');
        $userEmailChangeRequest->setToken($token);

        $this->getManager()->persist($userEmailChangeRequest);
        $this->getManager()->flush();
        
        $userEmailChangeRequestId = $userEmailChangeRequest->getId();
        
        $this->getManager()->clear();
  
        $this->assertEquals($token, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest')->find($userEmailChangeRequestId)->getToken());
    }    

}
