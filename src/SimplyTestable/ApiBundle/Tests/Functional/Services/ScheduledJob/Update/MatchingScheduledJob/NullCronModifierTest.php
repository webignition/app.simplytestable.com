<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Update\MatchingScheduledJob;

use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobServiceException;

class NullCronModifierTest extends MatchingScheduledJobTest {

    public function testUpdateToMatchingScheduledJobThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception',
            'Matching scheduled job exists',
            ScheduledJobServiceException::CODE_MATCHING_SCHEDULED_JOB_EXISTS
        );

        $this->getScheduledJobService()->update(
            $this->scheduledJob,
            $this->jobConfiguration2,
            '* * * * 1',
            null,
            false
        );
    }

    protected function getInitialCronModifier()
    {
        return null;
    }
}