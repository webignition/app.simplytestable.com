<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\MetaRedirect\DifferentUrl;

class AbsoluteUrlTest extends DifferentUrlTest {
    
    protected function getRedirectUrl() {
        return 'http://foo.example.com/';
    }

    protected function getEffectiveUrl() {
        return 'http://foo.example.com/';
    }    

    public function testWithAbsoluteUrl() {}
}
