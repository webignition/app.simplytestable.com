<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution\MetaRedirect\DifferentUrl;

class AbsoluteUrlTest extends DifferentUrlTest {

    protected function getRedirectUrl() {
        return 'http://foo.example.com/foo/';
    }

    protected function getEffectiveUrl() {
        return 'http://foo.example.com/foo/';
    }

    protected function getRootUrl() {
        return 'http://foo.example.com/';
    }

    public function testWithAbsoluteUrl() {}
}
