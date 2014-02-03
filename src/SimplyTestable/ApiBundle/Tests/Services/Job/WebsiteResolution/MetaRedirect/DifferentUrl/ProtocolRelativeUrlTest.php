<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\MetaRedirect\DifferentUrl;

class ProtocolRelativeUrlTest extends DifferentUrlTest {
    
    protected function getRedirectUrl() {
        return '//foo.example.com/';
    }

    protected function getEffectiveUrl() {
        return 'http://foo.example.com/';
    }    

    public function testWithProtocolRelativeUrl() {}
}
