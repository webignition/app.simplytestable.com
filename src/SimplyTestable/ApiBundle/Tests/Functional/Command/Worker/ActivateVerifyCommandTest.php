<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Worker;

use SimplyTestable\ApiBundle\Command\Worker\ActivateVerifyCommand;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Guzzle\Http\Message\Response as GuzzleResponse;

class ActivateVerifyCommandTest extends BaseSimplyTestableTestCase
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

        $this->command = $this->container->get('simplytestable.command.worker.activateverify');
        $this->workerFactory = new WorkerFactory($this->container);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
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
        $workerActivationRequestService = $this->container->get(
            'simplytestable.services.workeractivationrequestservice'
        );

        $this->queueHttpFixtures($httpFixtures);

        $token = 'token';
        $worker = $this->workerFactory->create();

        $request = $workerActivationRequestService->create($worker, $token);

        $this->assertEquals($worker->getHostname(), $request->getWorker()->getHostname());
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

        $this->assertEquals($worker->getHostname(), $request->getWorker()->getHostname());
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
