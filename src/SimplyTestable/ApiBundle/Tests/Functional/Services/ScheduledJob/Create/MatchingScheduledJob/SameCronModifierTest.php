<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Create\MatchingScheduledJob;

use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobServiceException;

class SameCronModifierTest extends MatchingScheduledJobTest {

    protected function getFirstCronModifier()
    {
        return 'foo';
    }

    public function testDoesNotThrowException() {
        $this->expectException(ScheduledJobServiceException::class);
        $this->expectExceptionMessage('Matching scheduled job exists');
        $this->expectExceptionCode(ScheduledJobServiceException::CODE_MATCHING_SCHEDULED_JOB_EXISTS);


        $this->getScheduledJobService()->create(
            $this->jobConfiguration,
            '* * * * *',
            $this->getFirstCronModifier(),
            true
        );
    }
}