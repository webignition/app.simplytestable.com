<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\Failure\ScheduledJobException\MatchingScheduledJob;

use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\Failure\ScheduledJobException\ExceptionTest;

abstract class MatchingScheduledJobExistsTest extends ExceptionTest {

    /**
     * @var User
     */
    private $user;

    protected function getRequestPostData() {
        $requestPostData = parent::getRequestPostData();

        if (!is_null($this->getNewCronModifier())) {
            $requestPostData['schedule-modifier'] = $this->getNewCronModifier();
        }

        return $requestPostData;
    }


    protected function getCurrentUser() {
        if (is_null($this->user)) {
            $userFactory = new UserFactory($this->container);

            $this->user = $userFactory->createAndActivateUser();
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

    abstract protected function getOriginalCronModifier();
    abstract protected function getNewCronModifier();


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

        $this->getScheduledJobService()->create(
            $jobConfiguration,
            $this->getRequestPostData()['schedule'],
            $this->getOriginalCronModifier(),
            true
        );
    }

}