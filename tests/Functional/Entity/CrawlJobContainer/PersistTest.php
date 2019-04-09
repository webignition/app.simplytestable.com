<?php

namespace App\Tests\Functional\Entity\CrawlJobContainer;

use App\Services\JobTypeService;
use App\Services\StateService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\CrawlJobContainer;
use App\Entity\Job\Job;
use App\Tests\Services\JobFactory;
use Doctrine\ORM\EntityManagerInterface;

class PersistTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $stateService = self::$container->get(StateService::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $jobTypeService = self::$container->get(JobTypeService::class);
        $jobFactory = self::$container->get(JobFactory::class);

        $parentJob = $jobFactory->create();

        $crawlJob = Job::create(
            $parentJob->getUser(),
            $parentJob->getWebsite(),
            $jobTypeService->getCrawlType(),
            $stateService->get(Job::STATE_STARTING),
            $parentJob->getParametersString()
        );

        $entityManager->persist($crawlJob);

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setParentJob($parentJob);
        $crawlJobContainer->setCrawlJob($crawlJob);

        $entityManager->persist($crawlJobContainer);
        $entityManager->flush();

        $this->assertNotNull($crawlJobContainer->getId());
    }
}
