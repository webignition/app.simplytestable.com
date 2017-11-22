<?php

namespace SimplyTestable\ApiBundle\Controller\Worker;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\Task\QueueService as TaskQueueService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TasksController
{
    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
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
        ResqueJobFactory $resqueJobFactory,
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
            $task->setState($stateService->get(TaskService::QUEUED_FOR_ASSIGNMENT_STATE));
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

        return new Response();
    }
}
