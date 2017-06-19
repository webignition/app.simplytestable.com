<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class FullSiteJobTest extends CommandTest
{
    /**
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();
        $this->queueResolveHttpFixture();

        $this->job = $this->createJobFactory()->create([
            JobFactory::KEY_TEST_TYPES => ['CSS Validation'],
        ]);

        $this->clearRedis();

        $this->assertReturnCode(0, array(
            'id' => $this->job->getId()
        ));
    }

    public function testJobStateIsResolved()
    {
        $this->assertEquals($this->getJobService()->getResolvedState(), $this->job->getState());
    }

    public function testNoTasksAreCreated()
    {
        $this->assertEquals(0, $this->job->getTasks()->count());
    }

    public function testResqueQueueContainsJobPreparationJob()
    {
        $this->assertTrue($this->getResqueQueueService()->contains(
            'job-prepare',
            array(
                'id' => $this->job->getId()
            )
        ));
    }
}
