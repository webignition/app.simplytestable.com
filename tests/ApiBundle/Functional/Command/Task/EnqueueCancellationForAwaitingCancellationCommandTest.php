<?php

namespace Tests\ApiBundle\Functional\Command\Task;

use SimplyTestable\ApiBundle\Command\Task\EnqueueCancellationForAwaitingCancellationCommand;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EnqueueCancellationForAwaitingCancellationCommandTest extends AbstractBaseTestCase
{
    /**
     * @var EnqueueCancellationForAwaitingCancellationCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get(EnqueueCancellationForAwaitingCancellationCommand::class);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $jobValues
     * @param bool $expectedResqueQueueIsEmpty
     * @param int[] $expectedTaskIndices
     */
    public function testRun($jobValues, $expectedResqueQueueIsEmpty, $expectedTaskIndices)
    {
        $resqueQueueService = $this->container->get(QueueService::class);

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->createResolveAndPrepare($jobValues);

        $expectedTaskIds = [];

        foreach ($job->getTaskIds() as $taskIndex => $taskId) {
            if (in_array($taskIndex, $expectedTaskIndices)) {
                $expectedTaskIds[] = $taskId;
            }
        }

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(EnqueueCancellationForAwaitingCancellationCommand::RETURN_CODE_OK, $returnCode);

        $this->assertEquals(
            $expectedResqueQueueIsEmpty,
            $resqueQueueService->isEmpty('task-cancel-collection')
        );

        if (!$expectedResqueQueueIsEmpty) {
            $this->assertTrue($resqueQueueService->contains(
                'task-cancel-collection',
                array(
                    'ids' => implode(',', $expectedTaskIds)
                )
            ));
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'no tasks awaiting cancellation' => [
                'jobValues' => [],
                'expectedResqueQueueIsEmpty' => true,
                'expectedTaskIndices' => [],
            ],
            'some tasks awaiting cancellation' => [
                'jobValues' => [
                    JobFactory::KEY_TASKS => [
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_AWAITING_CANCELLATION,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_AWAITING_CANCELLATION,
                        ],
                    ],
                ],
                'expectedResqueQueueIsEmpty' => false,
                'expectedTaskIndices' => [0, 2],
            ],
        ];
    }
}
