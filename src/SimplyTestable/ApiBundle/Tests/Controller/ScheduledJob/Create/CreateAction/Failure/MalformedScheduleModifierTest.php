<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\Failure;

use SimplyTestable\ApiBundle\Entity\User;

class MalformedScheduleModifierTest extends FailureTest {

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
        return 96;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Malformed schedule modifier';
    }

    protected function getRequestPostData() {
        $requestPostData = parent::getRequestPostData();
        $requestPostData['schedule'] = '* * * * *';
        $requestPostData['schedule-modifier'] = 'foo';

        return $requestPostData;
    }

    protected function preCallController() {
        $this->createJobConfiguration([
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $this->getCurrentUser());
    }
}