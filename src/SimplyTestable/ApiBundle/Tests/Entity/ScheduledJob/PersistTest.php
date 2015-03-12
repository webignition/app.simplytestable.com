<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\ScheduledJob;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;

class PersistTest extends ScheduledJobTest {

    /**
     * @var ScheduledJob
     */
    private $scheduledJob;

    public function setUp() {
        parent::setUp();

        $this->scheduledJob = $this->getScheduledJob();

        $this->getManager()->persist($this->scheduledJob->getCronJob());
        $this->getManager()->persist($this->scheduledJob->getJobConfiguration());
        $this->getManager()->persist($this->scheduledJob);
        $this->getManager()->flush();
    }


    public function testIsPersisted() {
        $this->assertNotNull($this->scheduledJob->getId());
    }

}
