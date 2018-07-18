<?php

namespace Tests\ApiBundle\Functional\Entity\CrawlJobContainer;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\StateService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class PersistTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $stateService = self::$container->get(StateService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobTypeService = self::$container->get(JobTypeService::class);

        $crawlJobType = $jobTypeService->getCrawlType();

        $jobFactory = new JobFactory(self::$container);

        $parentJob = $jobFactory->create();

        $crawlJob = new Job();
        $crawlJob->setType($crawlJobType);
        $crawlJob->setState($stateService->get(Job::STATE_STARTING));
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
