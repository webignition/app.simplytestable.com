<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\WebSite\IsPubliclyRoutable;


class EndsWithDotTest extends AbstractIsPubliclyRoutableTest {    
    
    public function testHttpHostEndsWithDotIsNotRoutable() {
        $this->assertIsNotRoutableForUrl('http://example./');        
    }
    
    public function testHttpHostEndsWithDotWithPathIsNotRoutable() {
        $this->assertIsNotRoutableForUrl('http://example./index.html');     
    }    
     
}
