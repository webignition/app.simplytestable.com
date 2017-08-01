<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Update\MatchingScheduledJob;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobServiceException;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Update\ServiceTest;

abstract class MatchingScheduledJobTest extends ServiceTest {

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration1;


    /**
     * @var JobConfiguration
     */
    protected $jobConfiguration2;


    /**
     * @var ScheduledJob
     */
    protected $scheduledJob;


    /**
     * @var User
     */
    private $user;

    public function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $this->user = $userFactory->createAndActivateUser();

        $this->jobConfiguration1 = $this->createJobConfiguration([
            'label' => 'foo1',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $this->user);

        $this->jobConfiguration2 = $this->createJobConfiguration([
            'label' => 'foo2',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'CSS validation' => []
            ],

        ], $this->user);

        $this->scheduledJob = $this->getScheduledJobService()->create(
            $this->jobConfiguration1,
            '* * * * *',
            null,
            true
        );

        $this->getScheduledJobService()->create(
            $this->jobConfiguration2,
            '* * * * 1',
            $this->getInitialCronModifier(),
            false
        );
    }

    abstract protected function getInitialCronModifier();



}