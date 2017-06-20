<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\Failure\ScheduledJobException\MatchingScheduledJob;

class SameCronModifierTest extends MatchingScheduledJobExistsTest {

    protected function getOriginalCronModifier()
    {
        return '[ `date +\%d` -le 7 ]';
    }

    protected function getNewCronModifier()
    {
        return $this->getOriginalCronModifier();
    }
}