<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Failure;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class MatchingScheduledJobTest extends FailureTest {

    /**
     * @var User
     */
    private $user;


    /**
     * @var JobConfiguration
     */
    private $jobConfiguration1;


    /**
     * @var JobConfiguration
     */
    private $jobConfiguration2;


    /**
     * @var ScheduledJob
     */
    private $scheduledJob1;


    /**
     * @var ScheduledJob
     */
    private $scheduledJob2;



    protected function getCurrentUser() {
        if (is_null($this->user)) {
            $this->user = $this->createAndActivateUser();
        }

        return $this->user;
    }

    protected function getHeaderErrorCode()
    {
        return 2;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Matching scheduled job exists';
    }


    protected function getScheduledJobId() {
        return $this->scheduledJob1->getId();
    }


    protected function preCallController() {
        $this->jobConfiguration1 = $this->createJobConfiguration([
            'label' => 'foo-1',
            'parameters' => 'parameters-1',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $this->getCurrentUser());


        $this->jobConfiguration2 = $this->createJobConfiguration([
            'label' => 'foo-2',
            'parameters' => 'parameters-2',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $this->getCurrentUser());

        $this->getScheduledJobService()->setUser($this->getCurrentUser());
        $this->scheduledJob1 = $this->getScheduledJobService()->create($this->jobConfiguration1);
        $this->scheduledJob2 = $this->getScheduledJobService()->create($this->jobConfiguration2);
    }


    protected function getRequestPostData() {
        $requestPostData = parent::getRequestPostData();
        $requestPostData['job-configuration'] = 'foo-2';
        return $requestPostData;
    }
}