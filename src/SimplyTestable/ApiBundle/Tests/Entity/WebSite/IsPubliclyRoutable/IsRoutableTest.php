<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\WebSite\IsPubliclyRoutable;

class IsRoutableTest extends AbstractIsPubliclyRoutableTest {

    public function testPlainAsciiIsPubliclyRoutable() {
        $this->assertIsRoutableForUrl('http://example.com/');   
    }   
   
    public function testUtf8IsPubliclyRoutable() {
        $this->assertIsRoutableForUrl('http://en.wikipedia.org/wiki/ɸ');
    }     
}
