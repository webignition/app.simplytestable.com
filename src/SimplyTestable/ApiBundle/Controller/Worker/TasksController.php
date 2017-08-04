<?php

namespace SimplyTestable\ApiBundle\Controller\Worker;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Services\WorkerService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\StateService;

use SimplyTestable\ApiBundle\Controller\ApiController;

class TasksController extends ApiController {

    public function __construct() {
        $this->setInputDefinitions([
            'requestAction' => new InputDefinition([
                new InputArgument('worker_hostname', InputArgument::REQUIRED, 'Hostname of worker making request'),
                new InputArgument('worker_token', InputArgument::REQUIRED, 'Requesting worker\'s auth token'),
                new InputArgument('limit', InputArgument::OPTIONAL, 'Number of tasks to request')
            ])
        ]);

        $this->setRequestTypes(array(
            'requestAction' => \Guzzle\Http\Message\Request::POST
        ));
    }


    public function requestAction()
    {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $worker_hostname = $this->getArguments('requestAction')->get('worker_hostname');

        if (!($this->getWorkerService()->has($worker_hostname))) {
            return $this->sendFailureResponse([
                'X-Message' => 'Invalid hostname "' . $worker_hostname . '"'
            ]);
        }

        $worker = $this->getWorkerService()->get($worker_hostname);

        if (!$worker->getState()->equals($this->getStateService()->fetch('worker-active'))) {
            return $this->sendFailureResponse([
                'X-Message' => 'Worker is not active',
                'X-Retryable' => '1'
            ]);
        }

        $worker_token = $this->getArguments('requestAction')->get('worker_token');

        if ($worker->getToken() != $worker_token) {
            return $this->sendFailureResponse([
                'X-Message' => 'Invalid token'
            ]);
        }

        $limit = filter_var($this->getArguments('requestAction')->get('limit'), FILTER_VALIDATE_INT, array(
            'options' => array(
                'default' => 0,
                'min_range' => 0
            )
        ));

        if ($limit === 0) {
            return $this->sendResponse();
        }

        if ($this->getResqueQueueService()->contains('task-assign-collection', ['worker' => $worker_hostname])) {
            return $this->sendResponse();
        }

        $this->getTaskQueueService()->setLimit($limit);
        $taskIds = $this->getTaskQueueService()->getNext();

        if (count($taskIds) == 0) {
            return $this->sendResponse();
        }

        $tasks = $this->getTaskService()->getEntityRepository()->getCollectionById($taskIds);

        foreach ($tasks as $task) {
            $task->setState($this->getTaskService()->getQueuedForAssignmentState());
            $this->getTaskService()->persistAndFlush($task);
        }

        $this->getResqueQueueService()->enqueue(
            $this->getResqueJobFactoryService()->create(
                'task-assign-collection',
                [
                    'ids' => implode(',', $taskIds),
                    'worker' => $worker_hostname
                ]
            )
        );

        return $this->sendResponse();
    }

    /**
     *
     * @return TaskService
     */
    private function getTaskService() {
        return $this->container->get('simplytestable.services.taskservice');
    }

    /**
     *
     * @return StateService
     */
    private function getStateService() {
        return $this->container->get('simplytestable.services.stateservice');
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Resque\QueueService
     */
    private function getResqueQueueService() {
        return $this->get('simplytestable.services.resque.queueService');
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Resque\JobFactoryService
     */
    private function getResqueJobFactoryService() {
        return $this->get('simplytestable.services.resque.jobFactoryService');
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Task\QueueService
     */
    private function getTaskQueueService() {
        return $this->get('simplytestable.services.task.queueservice');
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }
}

