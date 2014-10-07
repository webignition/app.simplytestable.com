<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class WorkerTaskAssignmentService extends WorkerTaskService {    
  
    const ASSIGN_COLLECTION_OK_STATUS_CODE = 0;
    const ASSIGN_COLLECTION_NO_WORKERS_STATUS_CODE = 1;
    const ASSIGN_COLLECTION_COULD_NOT_ASSIGN_TO_ANY_WORKERS_STATUS_CODE = 2;
    
    /**
     * 
     * @param array $tasks
     * @param Worker[] $workers
     * @return int
     */
    public function assignCollection($tasks, $workers) {
        $this->logger->info("WorkerTaskAssignmentService::assignCollection: Initialising");        
        $this->logger->info("WorkerTaskAssignmentService::assignCollection: [".count($workers)."] workers selected");

        $workerNames = [];
        foreach ($workers as $worker) {
            $workerNames[] = $worker->getHostname();
        }

        $this->logger->info("WorkerTaskAssignmentService::assignCollection: workers [" . implode(',', $workerNames) . "]");
        
        if (count($workers) == 0) {
            $this->logger->error("WorkerTaskAssignmentService::assignCollection: Cannot assign, no active workers.");
            return self::ASSIGN_COLLECTION_NO_WORKERS_STATUS_CODE;
        }
        
        $this->logger->info("WorkerTaskAssignmentService::assignCollection: Task collection count [".count($tasks)."]");
        if (count($tasks) === 0) {
            return self::ASSIGN_COLLECTION_OK_STATUS_CODE;
        }

        $taskGroups = $this->getTaskGroups($tasks, count($workers));
        $failedWorkers = [];
        $failedGroups = [];

        foreach ($taskGroups as $workerIndex => $tasks) {
            $worker = $workers[$workerIndex];

            if ($this->assignCollectionToWorker($tasks, $worker)) {
                foreach ($tasks as $task) {
                    $this->taskService->setStarted(
                        $task,
                        $worker,
                        $task->getRemoteId()
                    );

                    $this->taskService->getManager()->persist($task);
                }

                $this->taskService->getManager()->flush();
            } else {
                if (!in_array($worker->getHostname(), $failedWorkers)) {
                    $failedWorkers[] = $worker->getHostname();
                }

                $failedGroups[] = $tasks;
            }
        }

        if (count($failedGroups)) {
            $nonFailedWorkers = [];
            foreach ($workers as $worker) {
                if (!in_array($worker->getHostname(), $failedWorkers)) {
                    $nonFailedWorkers[] = $worker;
                }
            }

            /**
             * @var $failedTasks Task[]
             */
            $failedTasks = [];
            foreach ($failedGroups as $failedGroup) {
                $failedTasks = array_merge($failedTasks, $failedGroup);
            }

            if (count($nonFailedWorkers) > 0) {
                return $this->assignCollection($failedTasks, $nonFailedWorkers);
            }

            foreach ($failedTasks as $task) {
                $task->setState($this->taskService->getQueuedState());
                $this->taskService->persistAndFlush($task);
            }

            return self::ASSIGN_COLLECTION_COULD_NOT_ASSIGN_TO_ANY_WORKERS_STATUS_CODE;
        }

        return self::ASSIGN_COLLECTION_OK_STATUS_CODE;
    }


    /**
     * @param Task[] $tasks
     * @param int $workerCount
     * @return Task[]
     */
    private function getTaskGroups($tasks, $workerCount) {
        $taskGroups = [];

        foreach ($tasks as $taskIndex => $task) {
            $groupIndex = $taskIndex % $workerCount;

            if (!isset($taskGroups[$groupIndex])) {
                $taskGroups[$groupIndex] = [];
            }

            $taskGroups[$groupIndex][] = $task;
        }

        return $taskGroups;
    }

    /**
     * @param Task[] $tasks
     * @param Worker $worker
     * @return bool
     */
    private function assignCollectionToWorker($tasks, Worker $worker) {        
        $this->logger->info("WorkerTaskAssignmentService::assignCollectionToWorker: Trying worker with id [".$worker->getId()."] at host [".$worker->getHostname()."]");     
        
        $requestData = array();
        foreach ($tasks as $task) {
            /* @var $task Task */
            $postFields = array(
                'url' => $task->getUrl(),
                'type' => (string)$task->getType()            
            );
        
            if ($task->hasParameters()) {
                $postFields['parameters'] = $task->getParameters();
            }          
            
            $requestData[] = $postFields;
        }
        
        $requestUrl = $this->urlService->prepare('http://' . $worker->getHostname() . '/task/create/collection/');        
        
        $httpRequest = $this->httpClientService->postRequest($requestUrl, null, array(
            'tasks' => $requestData            
        ));
        
        try {
            $response = $httpRequest->send();
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {
            $this->logger->err("WorkerTaskAssignmentService::assignCollectionToWorker: " . $requestUrl . ": " . $curlException->getErrorNo().' '.$curlException->getError());
            return false;
        } catch (\Guzzle\Http\Exception\BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();
        }
        
        if (!$response->isSuccessful()) {
            $this->logger->err("WorkerTaskAssignmentService::assignCollectionToWorker " . $requestUrl . ": " . $response->getStatusCode()." ".$response->getReasonPhrase());     
            return false;
        }
        
        $this->logger->info("WorkerTaskAssignmentService::assignCollectionToWorker " . $requestUrl . ": " . $response->getStatusCode()." ".$response->getReasonPhrase());     
        
        $responseObject = json_decode($response->getBody());            
        foreach ($tasks as $task) {
            $this->setTaskRemoteIdFromRemoteCollection($task, $responseObject);
        }

        return true;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @param \stdClass $remoteCollection
     */
    private function setTaskRemoteIdFromRemoteCollection(Task $task, $remoteCollection) {
        foreach ($remoteCollection as $taskObject) {
            if ($task->getUrl() == $taskObject->url && (string)$task->getType() == $taskObject->type) {
                $task->setRemoteId($taskObject->id);
            }
        }
    }
}