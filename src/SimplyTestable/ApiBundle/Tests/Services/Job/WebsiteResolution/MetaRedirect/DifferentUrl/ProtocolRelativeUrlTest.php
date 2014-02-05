<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\MetaRedirect\DifferentUrl;

class ProtocolRelativeUrlTest extends DifferentUrlTest {
    
    protected function getRedirectUrl() {
        return '//foo.example.com/foo/';
    }

    protected function getEffectiveUrl() {
        return 'http://foo.example.com/foo/';
    }  
    
    protected function getRootUrl() {
        return 'http://foo.example.com/';
    }    

    public function testWithProtocolRelativeUrl() {}
}
