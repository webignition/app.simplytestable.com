<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Services\WorkerService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\JobService;

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
            'requestAction' => \Guzzle\Http\Message\Request::GET
        ));
    }  
    
    
    public function requestAction() {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
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

        $taskIds = $this->getTaskQueueService()->getNext($limit);
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


//
//        $taskIds = $this->getTaskQueueService()->getNext($limit);
//
//        $rawTaskCollection = $this->getTaskService()->getEntityRepository()->getCollectionById($taskIds);
//
//        $taskIdIndex = $taskIds;
//
//        $tasks = [];
//
//        while (count($taskIdIndex)) {
//            foreach ($taskIdIndex as $indexIndex => $taskId) {
//                foreach ($rawTaskCollection as $taskIndex => $task) {
//                    if ($task->getId() == $taskId) {
//                        $tasks[] = $task;
//                        unset($rawTaskCollection[$taskIndex]);
//                    }
//                }
//
//                unset($taskIdIndex[$indexIndex]);
//            }
//        }
//
//        $responseData = [];
//
//        foreach ($tasks as $task) {
//            /* @var $task Task */
//            $taskFields = array(
//                'url' => $task->getUrl(),
//                'type' => (string)$task->getType()
//            );
//
//            if ($task->hasParameters()) {
//                $taskFields['parameters'] = $task->getParameters();
//            }
//
//            $responseData[] = $taskFields;
//
//            $equivalentTasks = $this->getTaskService()->getEquivalentTasks($task->getUrl(), $task->getType(), $task->getParametersHash(), [
//                $this->getTaskService()->getQueuedForAssignmentState(),
//                $this->getTaskService()->getQueuedState()
//            ]);
//
//            foreach ($equivalentTasks as $equivalentTask) {
//                $this->getTaskService()->setStarted(
//                    $equivalentTask,
//                    $task->getWorker(),
//                    $task->getRemoteId()
//                );
//
//                $this->getTaskService()->persistAndFlush($equivalentTask);
//            }
//
//            $startedTasks = array_merge(array($task), $equivalentTasks);
//
//            foreach ($startedTasks as $startedTask) {
//                if ($startedTask->getJob()->getState()->getName() == 'job-queued') {
//                    $entityManager = $this->getContainer()->get('doctrine')->getManager();
//                    $startedTask->getJob()->setNextState();
//
//                    $entityManager->persist($startedTask->getJob());
//                    $entityManager->flush();
//                }
//            }
//        }
//
//        return $this->sendResponse($responseData);
    }

    
    
//    /**
//     *
//     * @return Worker
//     */
//    private function getWorker() {
//        return $this->get('simplytestable.services.workerservice')->fetch($this->workerHostname);
//    }
//
//
    /**
     *
     * @return TaskService
     */
    private function getTaskService() {
        return $this->container->get('simplytestable.services.taskservice');
    }
//
//
//    /**
//     *
//     * @return JobService
//     */
//    private function getJobService() {
//        return $this->container->get('simplytestable.services.jobservice');
//    }
//
//
//    /**
//     *
//     * @return \SimplyTestable\ApiBundle\Services\JobPreparationService
//     */
//    private function getJobPreparationService() {
//        return $this->container->get('simplytestable.services.jobpreparationservice');
//    }
//
//
//    /**
//     *
//     * @return TaskTypeService
//     */
//    private function getTaskTypeService() {
//        return $this->container->get('simplytestable.services.tasktypeservice');
//    }
//
    /**
     *
     * @return StateService
     */
    private function getStateService() {
        return $this->container->get('simplytestable.services.stateservice');
    }
//
//    /**
//     *
//     * @return \SimplyTestable\ApiBundle\Services\CrawlJobContainerService
//     */
//    private function getCrawlJobContainerService() {
//        return $this->container->get('simplytestable.services.crawljobcontainerservice');
//    }




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

