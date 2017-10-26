<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobPreparation\PrepareFromCrawl\ExceptionCases;

use SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception as JobPreparationException;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase
{
    public function testParentJobInWrongStateThrowsJobPreparationServiceException()
    {
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->create();
        $crawlJobContainer = $crawlJobContainerService->getForJob($job);

        try {
            $this->getJobPreparationService()->prepareFromCrawl($crawlJobContainer);
            $this->fail('\SimplyTestable\ApiBundle\Exception\Services\JobPreparation not thrown');
        } catch (JobPreparationException $jobPreparationServiceException) {
            $this->assertTrue($jobPreparationServiceException->isJobInWrongStateException());
        }
    }
}
