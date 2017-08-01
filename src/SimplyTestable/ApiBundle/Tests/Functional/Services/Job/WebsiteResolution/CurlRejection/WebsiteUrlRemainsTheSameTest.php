<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution\CurlRejection;

class WebsiteUrlRemainsTheSameTest extends CurlRejectionTest {

    protected function setUp() {
        parent::setUp();
        $this->assertEquals(self::SOURCE_URL, $this->job->getWebsite()->getCanonicalUrl());
    }
}
