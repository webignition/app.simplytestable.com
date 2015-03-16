<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Success\NoChanges;

class NoUpdatedValuesProvidedTest extends NoChangesTest {

    protected function getRequestPostData() {
        return [
            'job-configuration' => $this->getNewJobConfigurationLabel(),
            'schedule' => $this->originalSchedule,
            'is-recurring' => $this->originalIsRecurring
        ];
    }
}