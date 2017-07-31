<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class FullSiteJobTest extends CommandTest
{
    public function testCommand()
    {
        $jobFactory = new JobFactory($this->container);

        $this->queueResolveHttpFixture();

        $job = $jobFactory->create([
            JobFactory::KEY_TEST_TYPES => ['CSS Validation'],
        ]);

        $this->clearRedis();

        $returnCode = $this->execute(array(
            'id' => $job->getId()
        ));

        $this->assertEquals(0, $returnCode);
        $this->assertEquals($this->getJobService()->getResolvedState(), $job->getState());
        $this->assertEquals(0, $job->getTasks()->count());
        $this->assertTrue($this->getResqueQueueService()->contains(
            'job-prepare',
            array(
                'id' => $job->getId()
            )
        ));
    }
}
