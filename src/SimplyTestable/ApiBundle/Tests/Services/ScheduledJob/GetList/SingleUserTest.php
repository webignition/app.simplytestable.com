<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\GetList;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;

class SingleUserTest extends ServiceTest {

    /**
     * @var ScheduledJob[]
     */
    private $list;

    public function setUp() {
        parent::setUp();

        $user = $this->createAndActivateUser('user@example.com');

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
            true
        );

        $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * 0',
            true
        );

        $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * 1',
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