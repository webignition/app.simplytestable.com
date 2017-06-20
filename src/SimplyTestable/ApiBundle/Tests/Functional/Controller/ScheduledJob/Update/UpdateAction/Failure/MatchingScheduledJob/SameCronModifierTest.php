<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction\Failure\MatchingScheduledJob;

class SameCronModifierTest extends MatchingScheduledJobTest {

    protected function getCronModifier()
    {
        return 'foo';
    }
}