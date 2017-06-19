<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\ExceptionCases;

use SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception as JobPreparationException;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase
{
    public function testJobInWrongStateThrowsJobPreparationServiceException()
    {
        $job = $this->createJobFactory()->create();

        try {
            $this->getJobPreparationService()->prepare($job);
            $this->fail('\SimplyTestable\ApiBundle\Exception\Services\JobPreparation not thrown');
        } catch (JobPreparationException $jobPreparationServiceException) {
            $this->assertTrue($jobPreparationServiceException->isJobInWrongStateException());
        }
    }
}
