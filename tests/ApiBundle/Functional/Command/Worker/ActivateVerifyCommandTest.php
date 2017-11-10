<?php

namespace Tests\ApiBundle\Functional\Command\Worker;

use SimplyTestable\ApiBundle\Command\Worker\ActivateVerifyCommand;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
use Tests\ApiBundle\Factory\CurlExceptionFactory;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\WorkerFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Guzzle\Http\Message\Response as GuzzleResponse;

class ActivateVerifyCommandTest extends AbstractBaseTestCase
{
    /**
     * @var ActivateVerifyCommand
     */
    private $command;

    /**
     * @var WorkerFactory
     */
    private $workerFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get(ActivateVerifyCommand::class);
        $this->workerFactory = new WorkerFactory($this->container);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => 1,
        ]), new BufferedOutput());

        $this->assertEquals(
            ActivateVerifyCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    /**
     * @dataProvider runHttpErrorDataProvider
     *
     * @param array $httpFixtures
     * @param int $expectedReturnCode
     */
    public function testRunHttpError($httpFixtures, $expectedReturnCode)
    {
        $workerActivationRequestService = $this->container->get(WorkerActivationRequestService::class);

        $this->queueHttpFixtures($httpFixtures);

        $token = 'token';
        $worker = $this->workerFactory->create();

        $request = $workerActivationRequestService->create($worker, $token);

        $this->assertEquals($worker->getHostname(), $request->getWorker()->getHostname());
        $this->assertEquals(WorkerActivationRequestService::STARTING_STATE, $request->getState()->getName());

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $worker->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(
            $expectedReturnCode,
            $returnCode
        );
    }

    /**
     * @return array
     */
    public function runHttpErrorDataProvider()
    {
        return [
            'Bad Request' => [
                'httpFixtures' => [
                    GuzzleResponse::fromMessage('HTTP/1.1 400 Bad Request'),
                ],
                'expectedReturnCode' => 400,
            ],
            'Service Unavailable' => [
                'httpFixtures' => [
                    GuzzleResponse::fromMessage('HTTP/1.1 503 Service Unavailable'),
                ],
                'expectedReturnCode' => 503,
            ],
            'curl failure' => [
                'httpFixtures' => [
                    CurlExceptionFactory::create('Operation timed out', 28),
                ],
                'expectedReturnCode' => false,
            ],
        ];
    }

    public function testRunSuccess()
    {
        $workerActivationRequestService = $this->container->get(WorkerActivationRequestService::class);

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(),
        ]);

        $token = 'token';
        $worker = $this->workerFactory->create();

        $request = $workerActivationRequestService->create($worker, $token);

        $this->assertEquals($worker->getHostname(), $request->getWorker()->getHostname());
        $this->assertEquals(WorkerActivationRequestService::STARTING_STATE, $request->getState()->getName());

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $worker->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(
            0,
            $returnCode
        );
    }
}
