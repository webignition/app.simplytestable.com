<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Update\Success;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class JobConfigurationOnlyTest extends SuccessTest {

    /**
     * @var JobConfiguration
     */
    private $originalJobConfiguration;


    /**
     * @var JobConfiguration
     */
    private $updatedJobConfiguration;

    /**
     * @return JobConfiguration
     */
    protected function getOriginalJobConfiguration()
    {
        if (is_null($this->originalJobConfiguration)) {
            $this->originalJobConfiguration = $this->createJobConfiguration([
                'label' => 'foo1',
                'parameters' => 'parameters',
                'type' => 'Full site',
                'website' => 'http://example.com/',
                'task_configuration' => [
                    'HTML validation' => []
                ],

            ], $this->user);
        }

        return $this->originalJobConfiguration;
    }

    /**
     * @return JobConfiguration
     */
    protected function getUpdatedJobConfiguration()
    {
        if (is_null($this->updatedJobConfiguration)) {
            $this->updatedJobConfiguration = $this->createJobConfiguration([
                'label' => 'foo2',
                'parameters' => 'parameters',
                'type' => 'Full site',
                'website' => 'http://example.com/',
                'task_configuration' => [
                    'CSS validation' => []
                ],

            ], $this->user);
        }

        return $this->updatedJobConfiguration;
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
        return $this->getOriginalSchedule();
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