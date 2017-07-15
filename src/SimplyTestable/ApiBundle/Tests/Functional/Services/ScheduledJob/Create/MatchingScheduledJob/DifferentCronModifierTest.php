<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Create\MatchingScheduledJob;

use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobServiceException;

class DifferentCronModifierTest extends MatchingScheduledJobTest {

    protected function getFirstCronModifier()
    {
        return 'foo';
    }

    public function testDoesNotThrowException() {
//        $this->setExpectedException(
//            'SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception',
//            'Matching scheduled job exists',
//            ScheduledJobServiceException::CODE_MATCHING_SCHEDULED_JOB_EXISTS
//        );

        $this->getScheduledJobService()->create(
            $this->jobConfiguration,
            '* * * * *',
            $this->getFirstCronModifier() . 'bar',
            true
        );
    }
}