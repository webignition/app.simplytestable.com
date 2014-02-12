<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\CurlRejection;

class WebsiteUrlRemainsTheSameTest extends CurlRejectionTest {    
    
    public function setUp() {
        parent::setUp();        
        $this->assertEquals(self::SOURCE_URL, $this->job->getWebsite()->getCanonicalUrl());        
    }
}
