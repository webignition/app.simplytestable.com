<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Cancel;

use SimplyTestable\ApiBundle\Command\Task\Cancel\CollectionCommand;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CollectionCommandTest extends AbstractBaseTestCase
{
    /**
     * @var CollectionCommand
     */
    private $command;

    /**
     * @var WorkerFactory
     */
    private $workerFactory;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get('simplytestable.command.task.cancelcollection');

        $this->workerFactory = new WorkerFactory($this->container);
        $this->jobFactory = new JobFactory($this->container);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $this->command->run(new ArrayInput([
            'ids' => '1,2,3'
        ]), new BufferedOutput());

        $this->assertEquals(CollectionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $httpFixtures
     * @param array $workerValuesCollection
     * @param array $jobValues
     * @param string[] $expectedTaskStates
     */
    public function testRun($httpFixtures, $workerValuesCollection, $jobValues, $expectedTaskStates)
    {
        foreach ($workerValuesCollection as $workerValues) {
            $this->workerFactory->create($workerValues);
        }

        $job = $this->jobFactory->createResolveAndPrepare($jobValues);

        $this->queueHttpFixtures($httpFixtures);

        $returnCode = $this->command->run(new ArrayInput([
            'ids' => implode(',', $job->getTaskIds()),
        ]), new BufferedOutput());

        $this->assertEquals(CollectionCommand::RETURN_CODE_OK, $returnCode);

        foreach ($job->getTasks() as $taskIndex => $task) {
            $this->assertEquals(
                $expectedTaskStates[$taskIndex],
                $task->getState()->getName()
            );
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'tasks have no worker' => [
                'httpFixtures' => [],
                'workerValuesCollection' => [],
                'jobValues' => [],
                'expectedTaskStates' => [
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
            ],
            'tasks have workers' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(),
                    HttpFixtureFactory::createSuccessResponse(),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker0',
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker1',
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'worker2',
                    ],
                ],
                'jobValues' => [
                    JobFactory::KEY_TASKS => [
                        [
                            JobFactory::KEY_TASK_WORKER_HOSTNAME => 'worker0',
                        ],
                        [
                            JobFactory::KEY_TASK_WORKER_HOSTNAME => 'worker1',
                        ],
                        [
                            JobFactory::KEY_TASK_WORKER_HOSTNAME => 'worker2',
                        ],
                    ],
                ],
                'expectedTaskStates' => [
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
            ],
        ];
    }
}
