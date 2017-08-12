<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\CrawlJobContainer;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class PersistTest extends BaseSimplyTestableTestCase
{
    public function testPersist()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $crawlJobType = $jobTypeService->getByName(JobTypeService::CRAWL_NAME);

        $jobFactory = new JobFactory($this->container);

        $parentJob = $jobFactory->create();

        $crawlJob = new Job();
        $crawlJob->setType($crawlJobType);
        $crawlJob->setState($stateService->fetch(JobService::STARTING_STATE));
        $crawlJob->setUser($parentJob->getUser());
        $crawlJob->setWebsite($parentJob->getWebsite());

        $this->getManager()->persist($crawlJob);

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setParentJob($parentJob);
        $crawlJobContainer->setCrawlJob($crawlJob);

        $this->getManager()->persist($crawlJobContainer);
        $this->getManager()->flush();

        $this->assertNotNull($crawlJobContainer->getId());
    }
}
