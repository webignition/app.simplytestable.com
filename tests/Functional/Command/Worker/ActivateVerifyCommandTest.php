<?php

namespace App\Tests\Functional\Command\Worker;

use GuzzleHttp\Psr7\Response;
use App\Command\Worker\ActivateVerifyCommand;
use App\Entity\WorkerActivationRequest;
use App\Services\HttpClientService;
use App\Services\WorkerActivationRequestService;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\WorkerFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use App\Tests\Services\TestHttpClientService;

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
     * @var TestHttpClientService
     */
    private $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(ActivateVerifyCommand::class);
        $this->workerFactory = new WorkerFactory(self::$container);
        $this->httpClientService = self::$container->get(HttpClientService::class);
    }

    /**
     * @dataProvider runHttpErrorDataProvider
     *
     * @param array $httpFixtures
     * @param int $expectedReturnCode
     */
    public function testRunHttpError($httpFixtures, $expectedReturnCode)
    {
        $workerActivationRequestService = self::$container->get(WorkerActivationRequestService::class);

        $this->httpClientService->appendFixtures($httpFixtures);

        $token = 'token';
        $worker = $this->workerFactory->create();

        $request = $workerActivationRequestService->create($worker, $token);

        $this->assertEquals($worker->getHostname(), $request->getWorker()->getHostname());
        $this->assertEquals(WorkerActivationRequest::STATE_STARTING, $request->getState()->getName());

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
                    new Response(400)
                ],
                'expectedReturnCode' => 400,
            ],
            'Service Unavailable' => [
                'httpFixtures' => [
                    new Response(503),
                ],
                'expectedReturnCode' => 503,
            ],
            'curl failure' => [
                'httpFixtures' => [
                    ConnectExceptionFactory::create(28, 'Operation timed out'),
                ],
                'expectedReturnCode' => false,
            ],
        ];
    }

    public function testRunSuccess()
    {
        $workerActivationRequestService = self::$container->get(WorkerActivationRequestService::class);

        $this->httpClientService->appendFixtures([
            new Response(),
        ]);

        $token = 'token';
        $worker = $this->workerFactory->create();

        $request = $workerActivationRequestService->create($worker, $token);

        $this->assertEquals($worker->getHostname(), $request->getWorker()->getHostname());
        $this->assertEquals(WorkerActivationRequest::STATE_STARTING, $request->getState()->getName());

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $worker->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(
            0,
            $returnCode
        );
    }
}
