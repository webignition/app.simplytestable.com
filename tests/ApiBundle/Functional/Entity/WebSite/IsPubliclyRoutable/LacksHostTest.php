<?php

namespace Tests\ApiBundle\Functional\Entity\WebSite\IsPubliclyRoutable;


class LacksHostTest extends AbstractIsPubliclyRoutableTest {

    public function testTest() {
        $this->assertIsNotRoutableForUrl('/');
    }
}
