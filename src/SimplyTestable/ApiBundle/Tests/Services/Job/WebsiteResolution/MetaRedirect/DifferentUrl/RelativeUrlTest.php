<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\MetaRedirect\DifferentUrl;

class RelativeUrlTest extends DifferentUrlTest {
    
    protected function getRedirectUrl() {
        return '/foo/';
    }

    protected function getEffectiveUrl() {
        return 'http://example.com/foo/';
    }  
    
    protected function getRootUrl() {
        return 'http://example.com/';
    }    

    public function testWithRelativeUrl() {}
}
