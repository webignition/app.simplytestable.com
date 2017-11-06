<?php

namespace Tests\ApiBundle\Functional\Entity\ScheduledJob\Persist;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use Tests\ApiBundle\Functional\Entity\ScheduledJob\ScheduledJobTest;

abstract class PersistTest extends ScheduledJobTest
{
    /**
     * @var ScheduledJob
     */
    private $scheduledJob;

    protected function setUp()
    {
        parent::setUp();

        $this->scheduledJob = $this->getScheduledJob();

        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $entityManager->persist($this->scheduledJob->getCronJob());
        $entityManager->persist($this->scheduledJob->getJobConfiguration());
        $entityManager->persist($this->scheduledJob);
        $entityManager->flush();
    }

    public function testIsPersisted()
    {
        $this->assertNotNull($this->scheduledJob->getId());
    }
}