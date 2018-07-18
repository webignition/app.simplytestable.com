<?php

namespace Tests\AppBundle\Functional\Command\Task;

use AppBundle\Command\Task\EnqueueCancellationForAwaitingCancellationCommand;
use AppBundle\Entity\Task\Task;
use AppBundle\Services\Resque\QueueService;
use Tests\AppBundle\Factory\JobFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
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

        $this->command = self::$container->get(EnqueueCancellationForAwaitingCancellationCommand::class);
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
        $resqueQueueService = self::$container->get(QueueService::class);
        $resqueQueueService->getResque()->getQueue('task-cancel-collection')->clear();

        $jobFactory = new JobFactory(self::$container);
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
