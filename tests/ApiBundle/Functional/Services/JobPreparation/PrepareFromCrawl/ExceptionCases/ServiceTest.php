<?php

namespace Tests\ApiBundle\Functional\Services\JobPreparation\PrepareFromCrawl\ExceptionCases;

use SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception as JobPreparationException;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class ServiceTest extends AbstractBaseTestCase
{
    public function testParentJobInWrongStateThrowsJobPreparationServiceException()
    {
        $crawlJobContainerService = $this->container->get(CrawlJobContainerService::class);
        $jobPreparationService = $this->container->get(JobPreparationService::class);

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->create();
        $crawlJobContainer = $crawlJobContainerService->getForJob($job);

        try {
            $jobPreparationService->prepareFromCrawl($crawlJobContainer);
            $this->fail('\SimplyTestable\ApiBundle\Exception\Services\JobPreparation not thrown');
        } catch (JobPreparationException $jobPreparationServiceException) {
            $this->assertTrue($jobPreparationServiceException->isJobInWrongStateException());
        }
    }
}
