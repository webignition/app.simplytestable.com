<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class SingleUrlJobTest extends CommandTest
{
//    /**
//     *
//     * @var Job
//     */
//    private $job;
//
//    public function setUp()
//    {
//        parent::setUp();
//        $this->queueResolveHttpFixture();
//
//        $this->job = $this->createJobFactory()->create([
//            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
//            JobFactory::KEY_TEST_TYPES => ['CSS Validation'],
//            JobFactory::KEY_TEST_TYPE_OPTIONS => [
//                'CSS validation' => array(
//                    'ignore-common-cdns' => 1,
//                )
//            ],
//        ]);
//
//        $this->clearRedis();
//    }

    public function testCommand()
    {
        parent::setUp();
        $this->queueResolveHttpFixture();

        $job = $this->createJobFactory()->create([
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
