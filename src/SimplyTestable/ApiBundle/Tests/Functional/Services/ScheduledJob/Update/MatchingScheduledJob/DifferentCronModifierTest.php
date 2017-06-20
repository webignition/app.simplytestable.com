<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Update\MatchingScheduledJob;

use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobServiceException;

class DifferentCronModifierTest extends MatchingScheduledJobTest {

    public function testUpdateDoesNotThrowException() {
        $this->getScheduledJobService()->update(
            $this->scheduledJob,
            $this->jobConfiguration2,
            '* * * * 1',
            $this->getInitialCronModifier() . 'bar',
            false
        );
    }

    protected function getInitialCronModifier()
    {
        return 'foo';
    }
}