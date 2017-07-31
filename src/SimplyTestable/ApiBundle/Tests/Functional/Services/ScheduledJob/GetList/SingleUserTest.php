<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\GetList;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class SingleUserTest extends ServiceTest {

    /**
     * @var ScheduledJob[]
     */
    private $list;

    public function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->createAndActivateUser();

        $jobConfiguration = $this->createJobConfiguration([
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $user);

        $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * *',
            null,
            true
        );

        $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * 0',
            null,
            true
        );

        $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * 1',
            null,
            true
        );

        $this->getScheduledJobService()->setUser($user);
        $this->list = $this->getScheduledJobService()->getList();
    }


    public function testListSize() {
        $this->assertEquals(3, count($this->list));
    }

    public function testListContainsOnlyScheduledJobs() {
        foreach ($this->list as $listItem) {
            $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\ScheduledJob', $listItem);
        }
    }

}