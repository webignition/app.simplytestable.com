<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\Failure\ScheduledJobException\MatchingScheduledJob;

class NullCronModifierTest extends MatchingScheduledJobExistsTest {

    protected function getOriginalCronModifier()
    {
        return null;
    }

    protected function getNewCronModifier()
    {
        return null;
    }
}