<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\CurlRejection;

class RejectionReasonTest extends CurlRejectionTest {    
    
    public function setUp() {
        parent::setUp();
        $this->assertEquals('curl-' . $this->getTestStatusCode(), $this->getJobRejectionReasonService()->getForJob($this->job)->getReason());        
    }   
}
