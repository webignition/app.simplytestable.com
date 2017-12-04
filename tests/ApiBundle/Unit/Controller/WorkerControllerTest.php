<?php

namespace Tests\ApiBundle\Unit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Controller\WorkerController;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;

/**
 * @group Controller/WorkerController
 */
class WorkerControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testActivateActionInMaintenanceReadOnlyMode()
    {
        $workerController = $this->createWorkerController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $workerController->activateAction(new Request());
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
        $workerController = $this->createWorkerController();

        $request = new Request([], [
            'hostname' => $hostname,
            'token' => $token,
        ]);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $workerController->activateAction($request);
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
//
//    /**
//     * @dataProvider activateActionWorkerInWrongStateDataProvider
//     *
//     * @param string $stateName
//     */
//    public function testActivateActionSuccessWorkerInWrongState($stateName)
//    {
//        $resqueQueueService = $this->container->get(QueueService::class);
//        $entityManager = $this->container->get('doctrine.orm.entity_manager');
//        $workerActivationRequestRepository = $entityManager->getRepository(WorkerActivationRequest::class);
//
//        $hostname = 'foo.worker.simplytestable.com';
//        $token = 'token';
//
//        $workerFactory = new WorkerFactory($this->container);
//        $workerFactory->create([
//            WorkerFactory::KEY_HOSTNAME => $hostname,
//            WorkerFactory::KEY_TOKEN => $token,
//            WorkerFactory::KEY_STATE => $stateName,
//        ]);
//
//        $request = new Request([], [
//            'hostname' => $hostname,
//            'token' => $token,
//        ]);
//
//        $response = $this->workerController->activateAction($request);
//
//        $this->assertTrue($response->isSuccessful());
//        $this->assertEmpty($workerActivationRequestRepository->findAll());
//        $this->assertTrue($resqueQueueService->isEmpty('worker-activate-verify'));
//    }
//
//    /**
//     * @return array
//     */
//    public function activateActionWorkerInWrongStateDataProvider()
//    {
//        return [
//            Worker::STATE_ACTIVE => [
//                'stateName' => Worker::STATE_ACTIVE,
//            ],
//            Worker::STATE_OFFLINE => [
//                'stateName' => Worker::STATE_OFFLINE,
//            ],
//            Worker::STATE_DELETED => [
//                'stateName' => Worker::STATE_DELETED,
//            ],
//        ];
//    }
//
//    /**
//     * @dataProvider activateActionSuccessDataProvider
//     *
//     * @param string $existingActivationRequestStateName
//     */
//    public function testActivateActionSuccess($existingActivationRequestStateName)
//    {
//        $entityManager = $this->container->get('doctrine.orm.entity_manager');
//        $resqueQueueService = $this->container->get(QueueService::class);
//        $stateService = $this->container->get(StateService::class);
//        $workerActivationRequestRepository = $entityManager->getRepository(WorkerActivationRequest::class);
//
//        $this->assertEmpty($workerActivationRequestRepository->findAll());
//
//        $hostname = 'foo.worker.simplytestable.com';
//        $token = 'token';
//
//        $workerFactory = new WorkerFactory($this->container);
//        $worker = $workerFactory->create([
//            WorkerFactory::KEY_HOSTNAME => $hostname,
//            WorkerFactory::KEY_TOKEN => $token,
//            WorkerFactory::KEY_STATE => Worker::STATE_UNACTIVATED,
//        ]);
//
//        if (!empty($existingActivationRequestStateName)) {
//            $activationRequest = new WorkerActivationRequest();
//            $activationRequest->setState($stateService->get($existingActivationRequestStateName));
//            $activationRequest->setWorker($worker);
//            $activationRequest->setToken($token);
//
//            $entityManager->persist($activationRequest);
//            $entityManager->flush();
//        }
//
//        $request = new Request([], [
//            'hostname' => $hostname,
//            'token' => $token,
//        ]);
//
//        $response = $this->workerController->activateAction($request);
//
//        $this->assertTrue($response->isSuccessful());
//
//        $activationRequest = $workerActivationRequestRepository->findOneBy([
//            'worker' => $worker,
//        ]);
//
//        $this->assertInstanceOf(WorkerActivationRequest::class, $activationRequest);
//        $this->assertEquals($worker, $activationRequest->getWorker());
//
//        $this->assertTrue($resqueQueueService->contains(
//            'worker-activate-verify',
//            ['id' => $activationRequest->getWorker()->getId()]
//        ));
//    }
//
//    /**
//     * @return array
//     */
//    public function activateActionSuccessDataProvider()
//    {
//        return [
//            'no existing request' => [
//                'existingActivationRequestStateName' => null,
//            ],
//            WorkerActivationRequestService::STARTING_STATE => [
//                'existingActivationRequestStateName' => WorkerActivationRequestService::STARTING_STATE,
//            ],
//            WorkerActivationRequestService::VERIFIED_STATE => [
//                'existingActivationRequestStateName' => WorkerActivationRequestService::VERIFIED_STATE,
//            ],
//            WorkerActivationRequestService::FAILED_STATE => [
//                'existingActivationRequestStateName' => WorkerActivationRequestService::FAILED_STATE,
//            ],
//        ];
//    }

    /**
     * @param array $services
     *
     * @return WorkerController
     */
    private function createWorkerController($services = [])
    {
        if (!isset($services[ApplicationStateService::class])) {
            $services[ApplicationStateService::class] = MockFactory::createApplicationStateService();
        }

        if (!isset($services[EntityManagerInterface::class])) {
            $services[EntityManagerInterface::class] = MockFactory::createEntityManager();
        }

        if (!isset($services[ResqueQueueService::class])) {
            $services[ResqueQueueService::class] = MockFactory::createResqueQueueService();
        }

        if (!isset($services[ResqueJobFactory::class])) {
            $services[ResqueJobFactory::class] = MockFactory::createResqueJobFactory();
        }

        if (!isset($services[WorkerActivationRequestService::class])) {
            $services[WorkerActivationRequestService::class] = MockFactory::createWorkerActivationRequestService();
        }

        if (!isset($services[StateService::class])) {
            $services[StateService::class] = MockFactory::createStateService();
        }

        $workerController = new WorkerController(
            $services[ApplicationStateService::class],
            $services[EntityManagerInterface::class],
            $services[ResqueQueueService::class],
            $services[ResqueJobFactory::class],
            $services[WorkerActivationRequestService::class],
            $services[StateService::class]
        );

        return $workerController;
    }
}
