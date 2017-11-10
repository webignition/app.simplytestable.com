<?php

namespace SimplyTestable\ApiBundle\Controller\Worker;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\Task\QueueService as TaskQueueService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TasksController extends Controller
{
    public function requestAction(Request $request)
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $resqueQueueService = $this->container->get(ResqueQueueService::class);
        $resqueJobFactory = $this->container->get(JobFactory::class);
        $stateService = $this->container->get(StateService::class);
        $taskQueueService = $this->container->get(TaskQueueService::class);

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
