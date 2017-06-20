<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\WebSite\IsPubliclyRoutable;


class StartsWithDotTest extends AbstractIsPubliclyRoutableTest {

    public function testHttpHostStartsWithDotIsNotRoutable() {
        $this->assertIsNotRoutableForUrl('http://.example/');
    }

    public function testHttpHostStartsWithDotWithPathIsNotRoutable() {
        $this->assertIsNotRoutableForUrl('http://.example/index.html');
    }


}
