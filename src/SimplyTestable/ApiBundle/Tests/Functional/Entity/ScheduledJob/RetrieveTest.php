<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\ScheduledJob;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;

class RetrieveTest extends ScheduledJobTest {

    /**
     * @var ScheduledJob
     */
    private $originalScheduledJob;

    /**
     * @var ScheduledJob
     */
    private $retrievedScheduledJob;

    protected function setUp() {
        parent::setUp();

        $this->originalScheduledJob = $this->getScheduledJob();

        $this->getManager()->persist($this->originalScheduledJob->getCronJob());
        $this->getManager()->persist($this->originalScheduledJob->getJobConfiguration());
        $this->getManager()->persist($this->originalScheduledJob);
        $this->getManager()->flush();

        $scheduledJobId = $this->originalScheduledJob->getId();

        $this->getManager()->clear();

        $this->retrievedScheduledJob = $this->getManager()->getRepository('SimplyTestable\\ApiBundle\\Entity\\ScheduledJob')->find($scheduledJobId);
    }


    public function testOriginalAndRetrievedAreNotTheExactSameObject() {
        $this->assertNotEquals(
            spl_object_hash($this->originalScheduledJob),
            spl_object_hash($this->retrievedScheduledJob)
        );
    }

    public function testOriginalAndRetrievedAreTheSameEntity() {
        $this->assertEquals($this->originalScheduledJob->getId(), $this->retrievedScheduledJob->getId());
    }

//    public function testRetrievedHasTaskConfigurations() {
//        $this->assertEquals(1, count($this->retrievedConfiguration->getTaskConfigurations()));
//    }

}
