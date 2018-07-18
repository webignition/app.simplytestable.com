<?php

namespace Tests\AppBundle\Functional\Entity\ScheduledJob;

use AppBundle\Entity\ScheduledJob;

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

        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $scheduledJobRepository = $entityManager->getRepository(ScheduledJob::class);

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
