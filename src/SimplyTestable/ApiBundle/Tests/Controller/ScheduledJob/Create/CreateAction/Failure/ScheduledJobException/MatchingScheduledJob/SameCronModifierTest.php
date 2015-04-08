<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\Failure\ScheduledJobException\MatchingScheduledJob;

class SameCronModifierTest extends MatchingScheduledJobExistsTest {

    protected function getOriginalCronModifier()
    {
        return 'foo';
    }

    protected function getNewCronModifier()
    {
        return $this->getOriginalCronModifier();
    }
}