<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\WebSite\IsPubliclyRoutable;


class LacksHostTest extends AbstractIsPubliclyRoutableTest {

    public function testTest() {
        $this->assertIsNotRoutableForUrl('/');
    }
}
