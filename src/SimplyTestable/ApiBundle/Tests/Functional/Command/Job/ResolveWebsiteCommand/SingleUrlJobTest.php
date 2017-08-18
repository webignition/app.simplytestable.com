<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
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
            JobFactory::KEY_TEST_TYPES => [
                TaskTypeService::HTML_VALIDATION_TYPE,
                TaskTypeService::CSS_VALIDATION_TYPE,
            ],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                TaskTypeService::CSS_VALIDATION_TYPE => [
                    'ignore-common-cdns' => 1,
                ]
            ],
        ]);

        $this->clearRedis();

        $returnCode = $this->execute(array(
            'id' => $job->getId()
        ));

        $taskIds = [];
        $tasks = $job->getTasks();

        foreach ($tasks as $task) {
            $taskIds[] = $task->getId();
        }

        $htmlValidationTask = $tasks->get(0);
        $cssValidationTask = $tasks->get(1);

        $this->assertEquals(0, $returnCode);
        $this->assertEquals(JobService::QUEUED_STATE, $job->getState()->getName());
        $this->assertEquals(2, $tasks->count());
        $this->assertEquals($this->getTaskService()->getQueuedState(), $htmlValidationTask->getState());
        $this->assertTrue(is_array($cssValidationTask->getParameter('domains-to-ignore')));
        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-assign-collection',
            ['ids' => implode(',', $taskIds)]
        ));
    }
}
