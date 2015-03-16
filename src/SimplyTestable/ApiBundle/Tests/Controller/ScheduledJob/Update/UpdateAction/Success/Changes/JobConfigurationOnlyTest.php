<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Success\NoChanges;

use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Success\SuccessTest;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class JobConfigurationOnlyTest extends SuccessTest {

    /**
     * @var JobConfiguration
     */
    private $newJobConfiguration;


    protected function getRequestPostData() {
        return [
            'job-configuration' => $this->getNewJobConfigurationLabel(),
            'schedule' => $this->originalSchedule,
            'is-recurring' => $this->originalIsRecurring
        ];
    }

    protected function preCallController() {
        $this->newJobConfiguration = $this->createJobConfiguration([
            'label' => 'foo-new',
            'parameters' => 'parameters-new',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $this->user);
    }

    protected function getNewJobConfigurationLabel()
    {
        return $this->newJobConfiguration->getLabel();
    }

    protected function getNewSchedule()
    {
        return $this->originalSchedule;
    }

    protected function getNewIsRecurring()
    {
        return $this->originalIsRecurring;
    }
}