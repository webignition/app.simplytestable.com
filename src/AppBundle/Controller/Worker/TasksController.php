<?php

namespace AppBundle\Controller\Worker;

use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\Task\Task;
use AppBundle\Entity\Worker;
use AppBundle\Repository\TaskRepository;
use AppBundle\Resque\Job\Task\AssignCollectionJob;
use AppBundle\Services\ApplicationStateService;
use AppBundle\Services\Resque\QueueService as ResqueQueueService;
use AppBundle\Services\StateService;
use AppBundle\Services\Task\QueueService as TaskQueueService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TasksController
{
    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param ResqueQueueService $resqueQueueService
     * @param StateService $stateService
     * @param TaskQueueService $taskQueueService
     * @param Request $request
     *
     * @return Response
     */
    public function requestAction(
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        ResqueQueueService $resqueQueueService,
        StateService $stateService,
        TaskQueueService $taskQueueService,
        Request $request
    ) {
        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        /* @var TaskRepository $taskRepository */
        $taskRepository = $entityManager->getRepository(Task::class);
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
