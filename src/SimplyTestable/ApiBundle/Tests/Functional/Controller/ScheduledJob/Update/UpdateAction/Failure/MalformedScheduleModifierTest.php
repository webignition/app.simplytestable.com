<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction\Failure;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;

class MalformedScheduleModifierTest extends FailureTest {

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
        return 97;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Invalid schedule modifier';
    }


    protected function getScheduledJobId() {
        return $this->scheduledJob->getId();
    }


    protected function preCallController() {
        $jobConfiguration = $this->createJobConfiguration([
            'label' => 'foo',
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


    protected function getRequestPostData() {
        $requestPostData = parent::getRequestPostData();
        $requestPostData['job-configuration'] = 'foo';
        $requestPostData['schedule-modifier'] = 'bar';
        return $requestPostData;
    }
}