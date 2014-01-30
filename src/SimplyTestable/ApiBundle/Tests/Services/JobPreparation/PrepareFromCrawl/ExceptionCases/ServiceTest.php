<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\PrepareFromCrawl\ExceptionCases;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase {    
    
    const CANONICAL_URL = 'http://example.com';
    
    public function testParentJobInWrongStateThrowsJobPreparationServiceException() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL)); 
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);     
        
        try {            
            $this->getJobPreparationService()->prepareFromCrawl($crawlJobContainer);
            $this->fail('\SimplyTestable\ApiBundle\Exception\Services\JobPreparation not thrown');
        } catch (\SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception $jobPreparationServiceException) {
            $this->assertTrue($jobPreparationServiceException->isJobInWrongStateException());
        }        
    }

}
