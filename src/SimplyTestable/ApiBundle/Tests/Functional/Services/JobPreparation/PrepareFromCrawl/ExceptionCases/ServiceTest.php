<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobPreparation\PrepareFromCrawl\ExceptionCases;

use SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception as JobPreparationException;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase
{
    public function testParentJobInWrongStateThrowsJobPreparationServiceException()
    {
        $job = $this->createJobFactory()->create();
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);

        try {
            $this->getJobPreparationService()->prepareFromCrawl($crawlJobContainer);
            $this->fail('\SimplyTestable\ApiBundle\Exception\Services\JobPreparation not thrown');
        } catch (JobPreparationException $jobPreparationServiceException) {
            $this->assertTrue($jobPreparationServiceException->isJobInWrongStateException());
        }
    }
}
