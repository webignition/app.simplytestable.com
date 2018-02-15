<?php

namespace Tests\ApiBundle\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\WorkerController;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use Tests\ApiBundle\Factory\WorkerFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Controller/WorkerController
 */
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

        $this->workerController = $this->container->get(WorkerController::class);
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

    /**
     * @dataProvider activateActionWorkerInWrongStateDataProvider
     *
     * @param string $stateName
     */
    public function testActivateActionSuccessWorkerInWrongState($stateName)
    {
        $resqueQueueService = $this->container->get(QueueService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $workerActivationRequestRepository = $entityManager->getRepository(WorkerActivationRequest::class);

        $resqueQueueService->getResque()->getQueue('worker-activate-verify')->clear();

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
        $resqueQueueService = $this->container->get(QueueService::class);
        $stateService = $this->container->get(StateService::class);
        $workerActivationRequestRepository = $entityManager->getRepository(WorkerActivationRequest::class);

        $resqueQueueService->getResque()->getQueue('worker-activate-verify')->clear();

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
            WorkerActivationRequest::STATE_STARTING => [
                'existingActivationRequestStateName' => WorkerActivationRequest::STATE_STARTING,
            ],
            WorkerActivationRequest::STATE_VERIFIED => [
                'existingActivationRequestStateName' => WorkerActivationRequest::STATE_VERIFIED,
            ],
            WorkerActivationRequest::STATE_FAILED => [
                'existingActivationRequestStateName' => WorkerActivationRequest::STATE_FAILED,
            ],
        ];
    }
}
