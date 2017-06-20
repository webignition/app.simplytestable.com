<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\WebSite\IsPubliclyRoutable;


class EndsWithDotTest extends AbstractIsPubliclyRoutableTest {

    public function testHttpHostEndsWithDotIsNotRoutable() {
        $this->assertIsNotRoutableForUrl('http://example./');
    }

    public function testHttpHostEndsWithDotWithPathIsNotRoutable() {
        $this->assertIsNotRoutableForUrl('http://example./index.html');
    }

}
