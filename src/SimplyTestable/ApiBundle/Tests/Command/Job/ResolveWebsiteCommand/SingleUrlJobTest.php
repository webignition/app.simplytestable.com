<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class SingleUrlJobTest extends CommandTest
{
    /**
     *
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();
        $this->queueResolveHttpFixture();

        $this->job = $this->createJobFactory()->create([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
            JobFactory::KEY_TEST_TYPES => ['CSS Validation'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'CSS validation' => array(
                    'ignore-common-cdns' => 1,
                )
            ],
        ]);

        $this->clearRedis();

        $this->assertReturnCode(0, array(
            'id' => $this->job->getId()
        ));
    }

    public function testJobStateIsQueued()
    {
        $this->assertEquals($this->getJobService()->getQueuedState(), $this->job->getState());
    }

    public function testTaskIsCreated()
    {
        $this->assertEquals(1, $this->job->getTasks()->count());
    }

    public function testTaskIsQueued()
    {
        $this->assertEquals($this->getTaskService()->getQueuedState(), $this->job->getTasks()->first()->getState());
    }

    public function testDomainsToIgnoreAreSet()
    {
        $this->assertTrue(is_array($this->job->getTasks()->first()->getParameter('domains-to-ignore')));
    }

    public function testResqueQueueContainsTaskAssignCollectionJob()
    {
        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-assign-collection',
            ['ids' => implode(',', $this->getTaskService()->getEntityRepository()->getIdsByJob($this->job))]
        ));
    }
}
