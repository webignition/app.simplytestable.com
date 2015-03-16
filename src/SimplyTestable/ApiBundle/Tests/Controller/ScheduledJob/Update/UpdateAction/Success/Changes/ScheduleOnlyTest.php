<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Success\NoChanges;

use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Success\SuccessTest;

class ScheduleOnlyTest extends SuccessTest {

    protected function getRequestPostData() {
        return [
            'job-configuration' => $this->originalJobConfiguration->getLabel(),
            'schedule' => $this->getNewSchedule(),
            'is-recurring' => $this->originalIsRecurring
        ];
    }

    protected function getNewJobConfigurationLabel()
    {
        return $this->originalJobConfiguration->getLabel();
    }

    protected function getNewSchedule()
    {
        return '* * * * 1';
    }

    protected function getNewIsRecurring()
    {
        return $this->originalIsRecurring;
    }
}