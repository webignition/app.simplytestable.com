<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\ScheduledJob;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;

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

        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $this->originalScheduledJob = $this->getScheduledJob();

        $entityManager->persist($this->originalScheduledJob->getCronJob());
        $entityManager->persist($this->originalScheduledJob->getJobConfiguration());
        $entityManager->persist($this->originalScheduledJob);
        $entityManager->flush();

        $scheduledJobId = $this->originalScheduledJob->getId();

        $entityManager->clear();

        $scheduledJobRepository = $entityManager->getRepository(ScheduledJob::class);
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
