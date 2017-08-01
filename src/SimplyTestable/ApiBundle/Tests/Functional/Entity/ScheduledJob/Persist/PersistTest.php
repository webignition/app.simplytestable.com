<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\ScheduledJob\Persist;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Functional\Entity\ScheduledJob\ScheduledJobTest;

abstract class PersistTest extends ScheduledJobTest {

    /**
     * @var ScheduledJob
     */
    private $scheduledJob;

    protected function setUp() {
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
