<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\ExceptionCases;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase {    
    
    const CANONICAL_URL = 'http://example.com';
    
    public function testJobInWrongStateThrowsJobPreparationServiceException() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::CANONICAL_URL)); 
        
        try {
            $this->getJobPreparationService()->prepare($job);
            $this->fail('\SimplyTestable\ApiBundle\Exception\Services\JobPreparation not thrown');
        } catch (\SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception $jobPreparationServiceException) {
            $this->assertTrue($jobPreparationServiceException->isJobInWrongStateException());
        }        
    }

}
