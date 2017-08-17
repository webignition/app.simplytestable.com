<?php

namespace SimplyTestable\ApiBundle\Controller\Worker;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\Request;

class TasksController extends ApiController
{
    public function requestAction(Request $request)
    {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $workerService = $this->container->get('simplytestable.services.workerservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactoryservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $taskQueueService = $this->container->get('simplytestable.services.task.queueservice');

        $workerHostname = $request->request->get('worker_hostname');

        if (!($workerService->has($workerHostname))) {
            return $this->sendFailureResponse([
                'X-Message' => sprintf('Invalid hostname "%s"', $workerHostname)
            ]);
        }

        $worker = $workerService->get($workerHostname);

        if ('worker-active' !== $worker->getState()->getName()) {
            return $this->sendFailureResponse([
                'X-Message' => 'Worker is not active',
                'X-Retryable' => '1'
            ]);
        }

        $workerToken = $request->request->get('token');

        if ($worker->getToken() !== $workerToken) {
            return $this->sendFailureResponse([
                'X-Message' => 'Invalid token'
            ]);
        }

        $limit = filter_var($request->request->get('limit'), FILTER_VALIDATE_INT, array(
            'options' => array(
                'default' => 0,
                'min_range' => 0
            )
        ));

        if ($limit === 0) {
            return $this->sendResponse();
        }

        if ($resqueQueueService->contains('task-assign-collection', ['worker' => $workerHostname])) {
            return $this->sendResponse();
        }

        $taskQueueService->setLimit($limit);
        $taskIds = $taskQueueService->getNext();

        if (empty($taskIds)) {
            return $this->sendResponse();
        }

        $taskRepository = $entityManager->getRepository(Task::class);

        $tasks = $taskRepository->findBy([
            'id' => $taskIds,
        ]);

        foreach ($tasks as $task) {
            $task->setState($stateService->fetch(TaskService::QUEUED_FOR_ASSIGNMENT_STATE));
            $entityManager->persist($task);
            $entityManager->flush($task);
        }

        $resqueQueueService->enqueue(
            $resqueJobFactory->create(
                'task-assign-collection',
                [
                    'ids' => implode(',', $taskIds),
                    'worker' => $workerHostname,
                ]
            )
        );

        return $this->sendResponse();
    }
}
