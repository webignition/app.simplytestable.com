<?php

namespace App\Tests\Functional\Entity\ScheduledJob;

use App\Entity\ScheduledJob;
use App\Repository\ScheduledJobRepository;
use Doctrine\ORM\EntityManagerInterface;

class RetrieveTest extends ScheduledJobTest
{
    /**
     * @var ScheduledJob
     */
    private $originalScheduledJob;

    /**
     * @var ScheduledJob
     */
    private $retrievedScheduledJob;

    protected function setUp()
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        $scheduledJobRepository = self::$container->get(ScheduledJobRepository::class);

        $this->originalScheduledJob = $this->getScheduledJob();

        $entityManager->persist($this->originalScheduledJob->getCronJob());
        $entityManager->persist($this->originalScheduledJob->getJobConfiguration());
        $entityManager->persist($this->originalScheduledJob);
        $entityManager->flush();

        $scheduledJobId = $this->originalScheduledJob->getId();

        $entityManager->clear();

        $this->retrievedScheduledJob = $scheduledJobRepository->find($scheduledJobId);
    }

    public function testOriginalAndRetrievedAreNotTheExactSameObject()
    {
        $this->assertNotEquals(
            spl_object_hash($this->originalScheduledJob),
            spl_object_hash($this->retrievedScheduledJob)
        );
    }

    public function testOriginalAndRetrievedAreTheSameEntity()
    {
        $this->assertEquals($this->originalScheduledJob->getId(), $this->retrievedScheduledJob->getId());
    }
}
