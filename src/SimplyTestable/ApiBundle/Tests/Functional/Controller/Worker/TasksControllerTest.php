<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker;

use SimplyTestable\ApiBundle\Command\Maintenance\EnableBackupReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Controller\Worker\TasksController;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\Request;

class TasksControllerTest extends BaseSimplyTestableTestCase
{
    public function testRequestActionInMaintenanceReadOnlyMode()
    {
        $request = new Request();

        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();

        $tasksController = $this->createTasksController($request);

        $response = $tasksController->requestAction($request);
        $this->assertEquals(503, $response->getStatusCode());

        $maintenanceController->enableBackupReadOnlyAction();

        $response = $tasksController->requestAction($request);
        $this->assertEquals(503, $response->getStatusCode());
    }

    /**
     * @dataProvider requestActionInvalidWorkerHostnameDataProvider
     *
     * @param string[] $workerHostnames
     * @param string $workerHostname
     */
    public function testRequestActionInvalidWorkerHostname($workerHostnames, $workerHostname)
    {
        $workerFactory = new WorkerFactory($this->container);

        foreach ($workerHostnames as $hostname) {
            $workerFactory->create($hostname);
        }

        $request = new Request(
            [],
            [
                'worker_hostname' => $workerHostname,
            ]
        );
        $tasksController = $this->createTasksController($request);

        $response = $tasksController->requestAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertTrue($response->headers->has('x-message'));
        $this->assertEquals(
            'Invalid hostname "' . $workerHostname . '"',
            $response->headers->get('x-message')
        );
    }

    /**
     * @return array
     */
    public function requestActionInvalidWorkerHostnameDataProvider()
    {
        return [
            'no workers' => [
                'workerHostnames' => [],
                'workerHostname' => 'foo',
            ],
            'invalid hostname' => [
                'workerHostnames' => [
                    'foo',
                    'bar',
                ],
                'workerHostname' => 'foobar',
            ],
        ];
    }

    public function testRequestActionFoo()
    {
        //
    }

    /**
     * @param Request $request
     *
     * @return TasksController
     */
    private function createTasksController(Request $request)
    {
        $controller = new TasksController();
        $this->container->enterScope('request');
        $this->container->set('request', $request);

        $controller->setContainer($this->container);

        return $controller;
    }

//    public function testEnableBackupReadOnlyAction()
//    {
//        $this->controller->enableBackupReadOnlyAction();
//
//        $this->assertEquals(
//            EnableBackupReadOnlyCommand::STATE_MAINTENANCE_BACKUP_READ_ONLY,
//            file_get_contents($this->getStateResourcePath())
//        );
//    }
//
//    public function testEnableReadOnlyAction()
//    {
//        $this->controller->enableReadOnlyAction();
//
//        $this->assertEquals(
//            EnableReadOnlyCommand::STATE_MAINTENANCE_READ_ONLY,
//            file_get_contents($this->getStateResourcePath())
//        );
//    }
//
//    public function testDisableReadOnlyAction()
//    {
//        $this->controller->disableReadOnlyAction();
//
//        $this->assertEquals(
//            EnableReadOnlyCommand::STATE_ACTIVE,
//            file_get_contents($this->getStateResourcePath())
//        );
//    }
//
//    public function testLeaveReadOnlyAction()
//    {
//        $this->controller->leaveReadOnlyAction();
//
//        $this->assertEquals(
//            EnableReadOnlyCommand::STATE_ACTIVE,
//            file_get_contents($this->getStateResourcePath())
//        );
//    }
//
//    /**
//     * @return string
//     */
//    private function getStateResourcePath()
//    {
//        $kernel = $this->container->get('kernel');
//
//        return sprintf(
//            '%s%s',
//            $kernel->locateResource('@SimplyTestableApiBundle/Resources/config/state/'),
//            $kernel->getEnvironment()
//        );
//    }
}
