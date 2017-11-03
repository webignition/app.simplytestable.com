<?php

namespace Tests\ApiBundle\Functional\Entity\WebSite\IsPubliclyRoutable;


class HostLacksDotTest extends AbstractIsPubliclyRoutableTest {

    public function testHttpDotlessHostOnlyIsNotRoutable() {
        $this->assertIsNotRoutableForUrl('http://example/');
    }

    public function testHttpDotlessHostWithPathIsNotRoutable() {
        $this->assertIsNotRoutableForUrl('http://example/index.html');
    }
}
