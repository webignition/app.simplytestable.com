<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction\Success\NoChanges;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction\Success\SuccessTest;

class IsRecurringOnlyTest extends SuccessTest {

    protected function getRequestPostData() {
        return [
            'job-configuration' => $this->originalJobConfiguration->getLabel(),
            'schedule' => $this->originalSchedule,
            'is-recurring' => $this->getNewIsRecurring()
        ];
    }

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
        return !$this->originalIsRecurring;
    }

    protected function getNewCronModifier()
    {
        return $this->originalCronModifier;
    }
}