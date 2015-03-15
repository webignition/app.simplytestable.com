<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\Failure\ScheduledJobException;

use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;
use SimplyTestable\ApiBundle\Entity\User;

class MatchingScheduledJobExistsTest extends ExceptionTest {

    /**
     * @var User
     */
    private $user;


    protected function getCurrentUser() {
        if (is_null($this->user)) {
            $this->user = $this->createAndActivateUser('user@example.com');
        }

        return $this->user;
    }

    protected function getHeaderErrorCode()
    {
        return ScheduledJobException::CODE_MATCHING_SCHEDULED_JOB_EXISTS;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Matching scheduled job exists';
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

        $this->getScheduledJobService()->create($jobConfiguration, $this->getRequestPostData()['schedule'], true);
    }

}