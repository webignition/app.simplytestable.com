<?php

namespace Tests\ApiBundle\Functional\Command\Worker;

use SimplyTestable\ApiBundle\Command\Tasks\RequeueQueuedForAssignmentCommand;
use SimplyTestable\ApiBundle\Command\Worker\SetTokenFromActivationRequestCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Factory\WorkerActivationRequestFactory;
use Tests\ApiBundle\Factory\WorkerFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SetTokenFromActivationRequestCommandTest extends AbstractBaseTestCase
{
    /**
     * @var SetTokenFromActivationRequestCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get('simplytestable.command.worker.settokenfromactivationrequest');
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            SetTokenFromActivationRequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $workerValuesCollection
     * @param array $workerActivationRequestValuesCollection
     * @param string[] $expectedWorkerTokens
     */
    public function testRun(
        $workerValuesCollection,
        $workerActivationRequestValuesCollection,
        $expectedWorkerTokens
    ) {
        $workerFactory = new WorkerFactory($this->container);
        $workerActivationRequestFactory = new WorkerActivationRequestFactory($this->container);

        /* @var Worker[] $workers */
        $workers = [];

        foreach ($workerValuesCollection as $workerValues) {
            $workers[] = $workerFactory->create($workerValues);
        }

        foreach ($workerActivationRequestValuesCollection as $workerActivationRequestValues) {
            $workerActivationRequestFactory->create($workerActivationRequestValues);
        }

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            SetTokenFromActivationRequestCommand::RETURN_CODE_OK,
            $returnCode
        );

        foreach ($workers as $workerIndex => $worker) {
            $this->assertEquals($expectedWorkerTokens[$workerIndex], $worker->getToken());
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'no workers' => [
                'workerValuesCollection' => [],
                'workerActivationRequestValuesCollection' => [],
                'expectedWorkerTokens' => [],
            ],
            'with workers' => [
                'workerValuesCollection' => [
                    [
                        WorkerFactory::KEY_HOSTNAME => 'no-token-no-activation-request',
                        WorkerFactory::KEY_TOKEN => null,
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'no-token-has-activation-request',
                        WorkerFactory::KEY_TOKEN => null,
                    ],
                    [
                        WorkerFactory::KEY_HOSTNAME => 'has-token',
                        WorkerFactory::KEY_TOKEN => 'has-token-token',
                    ],
                ],
                'workerActivationRequestValuesCollection' => [
                    [
                        WorkerActivationRequestFactory::KEY_HOSTNAME => 'no-token-has-activation-request',
                        WorkerActivationRequestFactory::KEY_TOKEN => 'no-token-has-activation-request-token',
                    ],
                ],
                'expectedWorkerTokens' => [
                    null,
                    'no-token-has-activation-request-token',
                    'has-token-token',
                ],
            ],
        ];
    }
}
