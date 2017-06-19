<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;

abstract class RejectedTest extends WithScheduledJobTest
{
    abstract protected function getExpectedRejectionReason();

    protected function getJobListIndex()
    {
        return 0;
    }

    public function testJobIsRejected()
    {
        $job = $this->getJobService()->getEntityRepository()->findAll()[$this->getJobListIndex()];
        $this->assertTrue($this->getJobService()->isRejected($job));
    }

    public function testRejectionReason()
    {
        /* @var $job Job */
        $job = $this->getJobService()->getEntityRepository()->findAll()[$this->getJobListIndex()];

        $this->assertEquals(
            $this->getExpectedRejectionReason(),
            $this->getJobRejectionReasonService()->getForJob($job)->getReason()
        );
    }
}
