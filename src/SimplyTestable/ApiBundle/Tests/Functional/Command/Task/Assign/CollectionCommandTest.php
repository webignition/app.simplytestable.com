<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Assign;

use SimplyTestable\ApiBundle\Command\Task\Assign\CollectionCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CollectionCommandTest extends BaseSimplyTestableTestCase
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

        $this->command = new CollectionCommand();
        $this->command->setContainer($this->container);

        $this->workerFactory = new WorkerFactory($this->container);
        $this->jobFactory = new JobFactory($this->container);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();

        $returnCode = $this->command->run(new ArrayInput([
            'ids' => '1,2,3'
        ]), new BufferedOutput());

        $this->assertEquals(-1, $returnCode);
    }

    public function testRunWithNoWorkers()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $job = $this->jobFactory->createResolveAndPrepare();

        $userService->setUser($userService->getPublicUser());

        $returnCode = $this->command->run(new ArrayInput([
            'ids' => implode(',', $job->getTaskIds())
        ]), new BufferedOutput());

        $this->assertEquals(1, $returnCode);

        $this->assertTrue($resqueQueueService->contains(
            'task-assign-collection',
            [
                'ids' => implode(',', $job->getTaskIds())
            ]
        ));
    }

    public function testRunWithNoWorkersAvailable()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $job = $this->jobFactory->createResolveAndPrepare();

        $userService->setUser($userService->getPublicUser());

        $this->queueHttpFixtures([
            HttpFixtureFactory::createNotFoundResponse(),
            HttpFixtureFactory::createNotFoundResponse(),
            HttpFixtureFactory::createNotFoundResponse(),
        ]);

        $this->workerFactory->create([
            WorkerFactory::KEY_HOSTNAME => 'hydrogen.worker.example.com',
        ]);

        $this->workerFactory->create([
            WorkerFactory::KEY_HOSTNAME => 'lithium.worker.example.com',
        ]);

        $this->workerFactory->create([
            WorkerFactory::KEY_HOSTNAME => 'helium.worker.example.com',
        ]);

        $returnCode = $this->command->run(new ArrayInput([
            'ids' => implode(',', $job->getTaskIds())
        ]), new BufferedOutput());

        $this->assertEquals(2, $returnCode);

        $this->assertTrue($resqueQueueService->contains(
            'task-assign-collection',
            [
                'ids' => implode(',', $job->getTaskIds())
            ]
        ));
    }

    public function testAssignToSpecificWorker()
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $job = $this->jobFactory->createResolveAndPrepare();

        $userService->setUser($userService->getPublicUser());

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(
                'application/json',
                json_encode([
                    [
                        'id' => 1,
                        'url' => 'http://example.com/one',
                        'type' => 'html validation',
                    ],
                    [
                        'id' => 2,
                        'url' => 'http://example.com/bar%20foo',
                        'type' => 'html validation',
                    ],
                    [
                        'id' => 3,
                        'url' => 'http://example.com/foo bar',
                        'type' => 'html validation',
                    ],
                ])
            )
        ]);

        $this->workerFactory->create();
        $worker = $this->workerFactory->create([
            WorkerFactory::KEY_HOSTNAME => 'worker.example.com',
        ]);

        $returnCode = $this->command->run(new ArrayInput([
            'ids' => implode(',', $job->getTaskIds()),
            'worker' => $worker->getHostname()
        ]), new BufferedOutput());

        $this->assertEquals(0, $returnCode);

        foreach ($job->getTasks() as $task) {
            $this->assertEquals($worker->getHostname(), $task->getWorker()->getHostname());
            $this->assertEquals(TaskService::IN_PROGRESS_STATE, $task->getState()->getName());
        }
    }
}
