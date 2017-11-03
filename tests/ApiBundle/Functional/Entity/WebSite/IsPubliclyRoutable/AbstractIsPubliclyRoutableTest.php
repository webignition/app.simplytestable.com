<?php

namespace Tests\ApiBundle\Functional\Entity\WebSite\IsPubliclyRoutable;

use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\WebSite;

abstract class AbstractIsPubliclyRoutableTest extends AbstractBaseTestCase {

    protected function setUp() {
    }

    public function tearDown() {
    }

    protected function assertIsRoutableForUrl($url) {
        $webSite = new WebSite();
        $webSite->setCanonicalUrl($url);

        $this->assertTrue($webSite->isPubliclyRoutable());
    }

    protected function assertIsNotRoutableForUrl($url) {
        $webSite = new WebSite();
        $webSite->setCanonicalUrl($url);

        $this->assertFalse($webSite->isPubliclyRoutable());
    }
}
