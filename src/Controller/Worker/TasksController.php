<?php

namespace App\Controller\Worker;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Entity\Worker;
use App\Repository\TaskRepository;
use App\Resque\Job\Task\AssignCollectionJob;
use App\Services\ApplicationStateService;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\StateService;
use App\Services\Task\QueueService as TaskQueueService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TasksController
{
    public function requestAction(
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        ResqueQueueService $resqueQueueService,
        StateService $stateService,
        TaskQueueService $taskQueueService,
        TaskRepository $taskRepository,
        Request $request
    ): Response {
        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $workerRepository = $entityManager->getRepository(Worker::class);
        $workerHostname = $request->request->get('worker_hostname');

        /* @var Worker $worker */
        $worker = $workerRepository->findOneBy([
            'hostname' => $workerHostname,
        ]);

        if (empty($worker)) {
            return Response::create('', 400, [
                'X-Message' => sprintf('Invalid hostname "%s"', $workerHostname)
            ]);
        }

        if (Worker::STATE_ACTIVE !== $worker->getState()->getName()) {
            return Response::create('', 400, [
                'X-Message' => 'Worker is not active',
                'X-Retryable' => '1'
            ]);
        }

        $workerToken = $request->request->get('worker_token');

        if ($worker->getToken() !== $workerToken) {
            return Response::create('', 400, [
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
            return new Response();
        }

        if ($resqueQueueService->contains('task-assign-collection', ['worker' => $workerHostname])) {
            return new Response();
        }

        $taskQueueService->setLimit($limit);
        $taskIds = $taskQueueService->getNext();

        if (empty($taskIds)) {
            return new Response();
        }

        /* @var Task[] $tasks */
        $tasks = $taskRepository->findBy([
            'id' => $taskIds,
        ]);

        foreach ($tasks as $task) {
            $task->setState($stateService->get(Task::STATE_QUEUED_FOR_ASSIGNMENT));
            $entityManager->persist($task);
            $entityManager->flush();
        }

        $resqueQueueService->enqueue(new AssignCollectionJob([
            'ids' => implode(',', $taskIds),
            'worker' => $workerHostname,
        ]));

        return new Response();
    }
}
