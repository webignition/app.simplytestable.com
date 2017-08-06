<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command;

use SimplyTestable\ApiBundle\Command\WorkerActivateVerifyCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Guzzle\Http\Message\Response as GuzzleResponse;

class WorkerActivateVerifyCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var WorkerActivateVerifyCommand
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

        $this->command = new WorkerActivateVerifyCommand();
        $this->command->setContainer($this->container);

        $this->workerFactory = new WorkerFactory($this->container);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();

        $returnCode = $this->command->run(new ArrayInput([
            'id' => 1,
        ]), new BufferedOutput());

        $this->assertEquals(
            WorkerActivateVerifyCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    /**
     * @dataProvider runHttpErrorDataProvider
     *
     * @param array $httpFixtures
     * @param int $expectedReturnCode
     */
    public function testRunHttpError($httpFixtures, $expectedReturnCode)
    {
        $workerActivationRequestService = $this->container->get(
            'simplytestable.services.workeractivationrequestservice'
        );

        $this->queueHttpFixtures($httpFixtures);

        $token = 'token';
        $worker = $this->workerFactory->create();

        $request = $workerActivationRequestService->create($worker, $token);

        $this->assertTrue($request->getWorker()->equals($worker));
        $this->assertEquals($workerActivationRequestService->getStartingState(), $request->getState());

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
            'Not Found' => [
                'httpFixtures' => [
                    GuzzleResponse::fromMessage('HTTP/1.1 404 Not Found'),
                ],
                'expectedReturnCode' => 404,
            ],
            'Internal Server Error' => [
                'httpFixtures' => [
                    GuzzleResponse::fromMessage('HTTP/1.1 500 Internal Server Error'),
                ],
                'expectedReturnCode' => 500,
            ],
            'Service Unavailable' => [
                'httpFixtures' => [
                    GuzzleResponse::fromMessage('HTTP/1.1 503 Service Unavailable'),
                ],
                'expectedReturnCode' => 503,
            ],
        ];
    }

    public function testRunSuccess()
    {
        $workerActivationRequestService = $this->container->get(
            'simplytestable.services.workeractivationrequestservice'
        );

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(),
        ]);

        $token = 'token';
        $worker = $this->workerFactory->create();

        $request = $workerActivationRequestService->create($worker, $token);

        $this->assertTrue($request->getWorker()->equals($worker));
        $this->assertEquals($workerActivationRequestService->getStartingState(), $request->getState());

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $worker->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(
            0,
            $returnCode
        );
    }
}
