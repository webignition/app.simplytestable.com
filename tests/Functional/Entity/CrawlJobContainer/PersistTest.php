<?php

namespace App\Tests\Functional\Entity\CrawlJobContainer;

use App\Services\JobTypeService;
use App\Services\StateService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\CrawlJobContainer;
use App\Entity\Job\Job;
use App\Tests\Services\JobFactory;

class PersistTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $stateService = self::$container->get(StateService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobTypeService = self::$container->get(JobTypeService::class);

        $crawlJobType = $jobTypeService->getCrawlType();

        $jobFactory = self::$container->get(JobFactory::class);

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
