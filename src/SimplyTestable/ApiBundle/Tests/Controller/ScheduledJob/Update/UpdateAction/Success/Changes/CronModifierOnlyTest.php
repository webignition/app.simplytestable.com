<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Success\NoChanges;

use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Success\SuccessTest;

class CronModifierOnlyTest extends SuccessTest {

    protected function getRequestPostData() {
        return [
            'job-configuration' => $this->originalJobConfiguration->getLabel(),
            'schedule' => $this->originalSchedule,
            'is-recurring' => $this->getNewIsRecurring(),
            'schedule-modifier' => $this->getNewCronModifier()
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
        return '[ `date +\%d` -le 7 ]';
    }
}