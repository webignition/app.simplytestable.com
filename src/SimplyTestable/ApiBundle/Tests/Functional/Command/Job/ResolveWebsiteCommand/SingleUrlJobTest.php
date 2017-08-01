<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class SingleUrlJobTest extends CommandTest
{
    public function testCommand()
    {
        $jobFactory = new JobFactory($this->container);

        $this->queueHttpFixtures([
            HttpFixtureFactory::createStandardResolveResponse(),
        ]);

        $job = $jobFactory->create([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
            JobFactory::KEY_TEST_TYPES => ['CSS Validation'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'CSS validation' => array(
                    'ignore-common-cdns' => 1,
                )
            ],
        ]);

        $this->clearRedis();

        $returnCode = $this->execute(array(
            'id' => $job->getId()
        ));

        $tasks = $job->getTasks();
        $task = $tasks->first();

        $this->assertEquals(0, $returnCode);
        $this->assertEquals($this->getJobService()->getQueuedState(), $job->getState());
        $this->assertEquals(1, $tasks->count());
        $this->assertEquals($this->getTaskService()->getQueuedState(), $task->getState());
        $this->assertTrue(is_array($task->getParameter('domains-to-ignore')));
        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-assign-collection',
            ['ids' => implode(',', $this->getTaskService()->getEntityRepository()->getIdsByJob($job))]
        ));
    }
}
