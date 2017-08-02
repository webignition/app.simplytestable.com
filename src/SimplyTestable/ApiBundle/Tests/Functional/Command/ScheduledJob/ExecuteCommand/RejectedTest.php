<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;

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

        $this->assertEquals(JobService::REJECTED_STATE, $job->getState->getName());
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
