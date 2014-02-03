<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\MetaRedirect\DifferentUrl;

class RelativeUrlTest extends DifferentUrlTest {
    
    protected function getRedirectUrl() {
        return '/foo';
    }

    protected function getEffectiveUrl() {
        return 'http://example.com/foo';
    }    

    public function testWithRelativeUrl() {}
}
