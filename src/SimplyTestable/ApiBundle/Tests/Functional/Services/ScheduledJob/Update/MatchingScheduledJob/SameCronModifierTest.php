<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Update\MatchingScheduledJob;

use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobServiceException;

class SameCronModifierTest extends MatchingScheduledJobTest {

    public function testUpdateToMatchingScheduledJobThrowsException() {
        $this->expectException(ScheduledJobServiceException::class);
        $this->expectExceptionMessage('Matching scheduled job exists');
        $this->expectExceptionCode(ScheduledJobServiceException::CODE_MATCHING_SCHEDULED_JOB_EXISTS);

        $this->getScheduledJobService()->update(
            $this->scheduledJob,
            $this->jobConfiguration2,
            '* * * * 1',
            $this->getInitialCronModifier(),
            false
        );
    }

    protected function getInitialCronModifier()
    {
        return 'foo';
    }
}