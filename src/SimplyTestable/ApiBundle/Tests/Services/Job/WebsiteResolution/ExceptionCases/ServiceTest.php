<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\ExceptionCases;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase {    
    
    const CANONICAL_URL = 'http://example.com';
    
    public function testJobInWrongStateThrowsJobWebsiteResolutionException() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL)); 
        $job->setState($this->getJobService()->getCancelledState());
        $this->getJobService()->persistAndFlush($job);       
        
        try {
            $this->getJobWebsiteResolutionService()->resolve($job);
            $this->fail('\SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException not thrown');
        } catch (\SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException $websiteResolutionException) {
            $this->assertTrue($websiteResolutionException->isJobInWrongStateException());
        }        
    }

}
