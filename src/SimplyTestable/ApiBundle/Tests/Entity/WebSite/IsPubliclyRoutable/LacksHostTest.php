<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\WebSite\IsPubliclyRoutable;


class LacksHostTest extends AbstractIsPubliclyRoutableTest {    
    
    public function testTest() {
        $this->assertIsNotRoutableForUrl('/');        
    }    
}
