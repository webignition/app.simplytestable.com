<?php

namespace Tests\ApiBundle\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\WorkerController;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
use Tests\ApiBundle\Factory\WorkerFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class WorkerControllerTest extends AbstractBaseTestCase
{
    /**
     * @var WorkerController
     */
    private $workerController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->workerController = new WorkerController();
        $this->workerController->setContainer($this->container);
    }

    public function testRequest()
    {
        $hostname = 'worker-hostname';
        $token = 'worker-token';

        $workerFactory = new WorkerFactory($this->container);
        $workerFactory->create([
            WorkerFactory::KEY_HOSTNAME => $hostname,
            WorkerFactory::KEY_TOKEN => $token,
        ]);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('worker_activate');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'parameters' => [
                'hostname' => $hostname,
                'token' => $token,
            ],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testActivateActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        try {
            $this->workerController->activateAction(new Request());
            $this->fail('ServiceUnavailableHttpException not thrown');
        } catch (ServiceUnavailableHttpException $serviceUnavailableHttpException) {
            $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
        }
    }

    /**
     * @dataProvider activateActionBadRequestDataProvider
     *
     * @param string $hostname
     * @param string $token
     * @param string $expectedExceptionMessage
     */
    public function testActivateActionBadRequest($hostname, $token, $expectedExceptionMessage)
    {
        $request = new Request([], [
            'hostname' => $hostname,
            'token' => $token,
        ]);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->workerController->activateAction($request);
    }

    /**
     * @return array
     */
    public function activateActionBadRequestDataProvider()
    {
        return [
            'no hostname no token' => [
                'hostname' => null,
                'token' => null,
                'expectedExceptionMessage' => '"hostname" missing',
            ],
            'has hostname no token' => [
                'hostname' => 'foo.worker.simplytestable.com',
                'token' => null,
                'expectedExceptionMessage' => '"token" missing',
            ],
            'no hostname has token' => [
                'hostname' => null,
                'token' => 'abcdef',
                'expectedExceptionMessage' => '"hostname" missing',
            ],
        ];
    }

    /**
     * @dataProvider activateActionWorkerInWrongStateDataProvider
     *
     * @param string $stateName
     */
    public function testActivateActionSuccessWorkerInWrongState($stateName)
    {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $workerActivationRequestRepository = $entityManager->getRepository(WorkerActivationRequest::class);

        $hostname = 'foo.worker.simplytestable.com';
        $token = 'token';

        $workerFactory = new WorkerFactory($this->container);
        $workerFactory->create([
            WorkerFactory::KEY_HOSTNAME => $hostname,
            WorkerFactory::KEY_TOKEN => $token,
            WorkerFactory::KEY_STATE => $stateName,
        ]);

        $request = new Request([], [
            'hostname' => $hostname,
            'token' => $token,
        ]);

        $response = $this->workerController->activateAction($request);

        $this->assertTrue($response->isSuccessful());
        $this->assertEmpty($workerActivationRequestRepository->findAll());
        $this->assertTrue($resqueQueueService->isEmpty('worker-activate-verify'));
    }

    /**
     * @return array
     */
    public function activateActionWorkerInWrongStateDataProvider()
    {
        return [
            Worker::STATE_ACTIVE => [
                'stateName' => Worker::STATE_ACTIVE,
            ],
            Worker::STATE_OFFLINE => [
                'stateName' => Worker::STATE_OFFLINE,
            ],
            Worker::STATE_DELETED => [
                'stateName' => Worker::STATE_DELETED,
            ],
        ];
    }

    /**
     * @dataProvider activateActionSuccessDataProvider
     *
     * @param string $existingActivationRequestStateName
     */
    public function testActivateActionSuccess($existingActivationRequestStateName)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $workerActivationRequestRepository = $entityManager->getRepository(WorkerActivationRequest::class);

        $this->assertEmpty($workerActivationRequestRepository->findAll());

        $hostname = 'foo.worker.simplytestable.com';
        $token = 'token';

        $workerFactory = new WorkerFactory($this->container);
        $worker = $workerFactory->create([
            WorkerFactory::KEY_HOSTNAME => $hostname,
            WorkerFactory::KEY_TOKEN => $token,
            WorkerFactory::KEY_STATE => Worker::STATE_UNACTIVATED,
        ]);

        if (!empty($existingActivationRequestStateName)) {
            $activationRequest = new WorkerActivationRequest();
            $activationRequest->setState($stateService->get($existingActivationRequestStateName));
            $activationRequest->setWorker($worker);
            $activationRequest->setToken($token);

            $entityManager->persist($activationRequest);
            $entityManager->flush();
        }

        $request = new Request([], [
            'hostname' => $hostname,
            'token' => $token,
        ]);

        $response = $this->workerController->activateAction($request);

        $this->assertTrue($response->isSuccessful());

        $activationRequest = $workerActivationRequestRepository->findOneBy([
            'worker' => $worker,
        ]);

        $this->assertInstanceOf(WorkerActivationRequest::class, $activationRequest);
        $this->assertEquals($worker, $activationRequest->getWorker());

        $this->assertTrue($resqueQueueService->contains(
            'worker-activate-verify',
            ['id' => $activationRequest->getWorker()->getId()]
        ));
    }

    /**
     * @return array
     */
    public function activateActionSuccessDataProvider()
    {
        return [
            'no existing request' => [
                'existingActivationRequestStateName' => null,
            ],
            WorkerActivationRequestService::STARTING_STATE => [
                'existingActivationRequestStateName' => WorkerActivationRequestService::STARTING_STATE,
            ],
            WorkerActivationRequestService::VERIFIED_STATE => [
                'existingActivationRequestStateName' => WorkerActivationRequestService::VERIFIED_STATE,
            ],
            WorkerActivationRequestService::FAILED_STATE => [
                'existingActivationRequestStateName' => WorkerActivationRequestService::FAILED_STATE,
            ],
        ];
    }
}
