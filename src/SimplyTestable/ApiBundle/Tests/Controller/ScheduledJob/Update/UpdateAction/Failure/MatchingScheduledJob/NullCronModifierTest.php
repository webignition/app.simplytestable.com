<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Failure\MatchingScheduledJob;

class NullCronModifierTest extends MatchingScheduledJobTest {

    protected function getCronModifier()
    {
        return null;
    }
}