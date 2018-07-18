<?php

namespace App\Tests\Unit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Controller\WorkerController;
use App\Services\ApplicationStateService;
use App\Services\StateService;
use App\Services\WorkerActivationRequestService;
use App\Tests\Factory\MockFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use App\Services\Resque\QueueService as ResqueQueueService;

/**
 * @group Controller/WorkerController
 */
class WorkerControllerTest extends \PHPUnit\Framework\TestCase
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
            $services[WorkerActivationRequestService::class],
            $services[StateService::class]
        );

        return $workerController;
    }
}
