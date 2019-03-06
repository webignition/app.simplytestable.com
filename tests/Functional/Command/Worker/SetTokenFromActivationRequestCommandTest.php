<?php

namespace App\Tests\Functional\Command\Worker;

use App\Command\Worker\SetTokenFromActivationRequestCommand;
use App\Entity\Worker;
use App\Tests\Services\WorkerActivationRequestFactory;
use App\Tests\Services\WorkerFactory;
use App\Tests\Functional\AbstractBaseTestCase;
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

        $this->command = self::$container->get(SetTokenFromActivationRequestCommand::class);
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
        $workerFactory = self::$container->get(WorkerFactory::class);
        $workerActivationRequestFactory = self::$container->get(WorkerActivationRequestFactory::class);

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
