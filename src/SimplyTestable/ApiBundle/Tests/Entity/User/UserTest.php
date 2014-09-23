<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\User;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\User;

class UserTest extends BaseSimplyTestableTestCase {        
    
    public function testUtf8Email() {        
        $email = 'ɸ@example.com';
        
        $user = $this->getUserService()->create($email, 'password');
        $userId = $user->getId();
       
        $this->getManager()->clear();
        $this->assertEquals($email, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\User')->find($userId)->getEmail());
    } 
}
