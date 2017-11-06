<?php

namespace Tests\ApiBundle\Functional\Entity\WebSite\IsPubliclyRoutable;

class LocalhostTest extends AbstractIsPubliclyRoutableTest {

    public function testHttpLocalHostOnlyIsNotRoutable() {
        $this->assertIsNotRoutableForUrl('http://localhost/');
    }

    public function testHttpLocalHostWithPathIsNotRoutable() {
        $this->assertIsNotRoutableForUrl('http://localhost/index.html');
    }
}