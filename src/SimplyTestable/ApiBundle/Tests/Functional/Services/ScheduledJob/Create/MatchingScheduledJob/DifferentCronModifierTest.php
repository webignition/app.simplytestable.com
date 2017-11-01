<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Create\MatchingScheduledJob;

class DifferentCronModifierTest extends MatchingScheduledJobTest {

    protected function getFirstCronModifier()
    {
        return 'foo';
    }

    public function testDoesNotThrowException() {
        $this->getScheduledJobService()->create(
            $this->jobConfiguration,
            '* * * * *',
            $this->getFirstCronModifier() . 'bar',
            true
        );
    }
}