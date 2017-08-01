<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\WebSite\IsPubliclyRoutable;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\WebSite;

abstract class AbstractIsPubliclyRoutableTest extends BaseSimplyTestableTestCase {

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
