<?php

namespace Tests\ApiBundle\Functional\Entity\CrawlJobContainer;

use SimplyTestable\ApiBundle\Services\JobService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class PersistTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');

        $crawlJobType = $jobTypeService->getCrawlType();

        $jobFactory = new JobFactory($this->container);

        $parentJob = $jobFactory->create();

        $crawlJob = new Job();
        $crawlJob->setType($crawlJobType);
        $crawlJob->setState($stateService->get(JobService::STARTING_STATE));
        $crawlJob->setUser($parentJob->getUser());
        $crawlJob->setWebsite($parentJob->getWebsite());

        $entityManager->persist($crawlJob);

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setParentJob($parentJob);
        $crawlJobContainer->setCrawlJob($crawlJob);

        $entityManager->persist($crawlJobContainer);
        $entityManager->flush();

        $this->assertNotNull($crawlJobContainer->getId());
    }
}
