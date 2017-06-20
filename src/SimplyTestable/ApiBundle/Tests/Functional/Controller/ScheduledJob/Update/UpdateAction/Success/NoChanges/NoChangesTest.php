<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction\Success\NoChanges;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction\Success\SuccessTest;

abstract class NoChangesTest extends SuccessTest {

    protected function getNewJobConfigurationLabel()
    {
        return $this->originalJobConfiguration->getLabel();
    }

    protected function getNewSchedule()
    {
        return $this->originalSchedule;
    }

    protected function getNewIsRecurring()
    {
        return $this->originalIsRecurring;
    }

    protected function getNewCronModifier()
    {
        return $this->originalCronModifier;
    }
}