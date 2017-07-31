<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\Failure;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class MalformedScheduleModifierTest extends FailureTest {

    /**
     * @var User
     */
    private $user;


    protected function getCurrentUser() {
        if (is_null($this->user)) {
            $userFactory = new UserFactory($this->container);
            $this->user = $userFactory->createAndActivateUser();
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