<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

abstract class WithScheduledJobTest extends CommandTest {

    /**
     * @var User
     */
    private $user;


    /**
     * @var ScheduledJob
     */
    protected $scheduledJob;

    protected function preCall() {
        $jobConfiguration = $this->createJobConfiguration($this->getCreateJobConfigurationArray(), $this->getUser());

        $this->scheduledJob = $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * *',
            true
        );
    }

    /**
     * @return array
     */
    protected function getCreateJobConfigurationArray() {
        return [
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => $this->getJobConfigurationJobType(),
            'website' => $this->getJobConfigurationWebsite(),
            'task_configuration' => [
                'HTML validation' => [],
                'CSS validation' => []
            ],

        ];
    }


    /**
     * @return User
     */
    protected function getUser() {
        if (is_null($this->user)) {
            $this->user = $this->getJobConfigurationUser();
        }

        return $this->user;
    }


    protected function getJobConfigurationWebsite() {
        return 'http://example.com/';
    }


    /**
     * @return User
     */
    protected function getJobConfigurationUser() {
        $userFactory = new UserFactory($this->container);

        return $userFactory->createAndActivateUser();
    }


    /**
     * @return string
     */
    protected function getJobConfigurationJobType() {
        return JobTypeService::FULL_SITE_NAME;
    }


    protected function getScheduledJobId()
    {
        return $this->scheduledJob->getId();
    }

}
