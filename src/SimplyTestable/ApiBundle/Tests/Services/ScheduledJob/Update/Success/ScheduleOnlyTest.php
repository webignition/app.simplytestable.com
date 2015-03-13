<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\Update\Success;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class ScheduleOnlyTest extends SuccessTest {

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;

    /**
     * @return JobConfiguration
     */
    protected function getOriginalJobConfiguration()
    {
        if (is_null($this->jobConfiguration)) {
            $this->jobConfiguration = $this->createJobConfiguration([
                'label' => 'foo',
                'parameters' => 'parameters',
                'type' => 'Full site',
                'website' => 'http://example.com/',
                'task_configuration' => [
                    'HTML validation' => []
                ],

            ], $this->user);
        }

        return $this->jobConfiguration;
    }

    /**
     * @return JobConfiguration
     */
    protected function getUpdatedJobConfiguration()
    {
        return $this->getOriginalJobConfiguration();
    }

    /**
     * @return string
     */
    protected function getOriginalSchedule()
    {
        return '* * * * *';
    }

    /**
     * @return string
     */
    protected function getUpdatedSchedule()
    {
        return '* * * * 1';
    }

    /**
     * @return bool
     */
    protected function getOriginalIsRecurring()
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function getUpdatedIsRecurring()
    {
        return $this->getOriginalIsRecurring();
    }
}