<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Failure;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;

class UnknownJobConfigurationTest extends FailureTest {

    /**
     * @var User
     */
    private $user;

    /**
     * @var ScheduledJob
     */
    private $scheduledJob;

    protected function getCurrentUser() {
        if (is_null($this->user)) {
            $this->user = $this->createAndActivateUser();
        }

        return $this->user;
    }

    protected function getHeaderErrorCode()
    {
        return 99;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Unknown job configuration';
    }


    protected function getScheduledJobId() {
        return $this->scheduledJob->getId();
    }


    protected function preCallController() {
        $jobConfiguration = $this->createJobConfiguration([
            'label' => 'foo-minus-one',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $this->getCurrentUser());

        $this->getScheduledJobService()->setUser($this->getCurrentUser());
        $this->scheduledJob = $this->getScheduledJobService()->create($jobConfiguration);
    }
}