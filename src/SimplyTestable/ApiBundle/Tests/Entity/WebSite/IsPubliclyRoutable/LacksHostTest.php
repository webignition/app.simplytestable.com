<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\WebSite\IsPubliclyRoutable;


class HostLacksDotTest extends AbstractIsPubliclyRoutableTest {    
    
    public function testTest() {
        $this->assertIsNotRoutableForUrl('/');        
    }    
}
