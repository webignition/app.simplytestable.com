<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;
use SimplyTestable\ApiBundle\Entity\TimePeriod;


class WorkerTaskAssignmentService extends WorkerTaskService {    
  
    const ASSIGN_COLLECTION_OK_STATUS_CODE = 0;
    const ASSIGN_COLLECTION_NO_WORKERS_STATUS_CODE = 1;
    const ASSIGN_COLLECTION_COULD_NOT_ASSIGN_TO_ANY_WORKERS_STATUS_CODE = 2;
    
    
    /**
     * Assign a task to a to-be-chosen worker.
     * 
     * @param Task $task
     * @param array $workers
     * @return int
     * 
     * return codes:
     * 0: ok
     * 1: not ready to be assigned (in wrong state)
     * 2: cannot assign, no workers
     * 3: could not assign to any workers
     */
    public function assign(Task $task, $workers) {        
        $this->logger->info("WorkerTaskAssignmentService::assign: Initialising");
        $this->logger->info("WorkerTaskAssignmentService::assign: Processing task [".$task->getId()."] [".$task->getType()."] [".$task->getUrl()."]");
        
        if (!$this->canTaskBeAssigned($task)) {
            $this->logger->err("WorkerTaskAssignmentService::assign: Task [".$task->getId()."] is not queued, nothing to do.");
            return 1;
        }                    
        
        if (count($workers) == 0) {
            $this->logger->err("WorkerTaskAssignmentService::assign: Cannot assign, no active workers.");
            return 2;
        }             
        
        foreach ($workers as $worker) {            
            $remoteTaskId = $this->assignToWorker($task, $worker);        
            if (is_int($remoteTaskId)) {
                $this->taskService->setStarted(
                    $task,
                    $worker,
                    $remoteTaskId
                );
                
                $this->taskService->persistAndFlush($task);
                
                $this->logger->info("WorkerTaskAssignmentService::assign: Succeeded with worker with id [".$worker->getId()."] at host [".$worker->getHostname()."]");
                return 0;
            }         
        }

        return 3;        
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @return boolean
     */
    private function canTaskBeAssigned(Task $task) {        
        return $this->taskService->isQueued($task) || $this->taskService->isQueuedForAssignment($task);       
    }
    
    
    /**
     * 
     * @param array $tasks
     * @param array $workers
     * @return int
     * 
     * return codes:
     * 0: ok
     * 1: cannot assign, no workers
     * 2: could not assign to any workers
     */
    public function assignCollection($tasks, $workers) {        
        $this->logger->info("WorkerTaskAssignmentService::assignCollection: Initialising");        
        $this->logger->info("WorkerTaskAssignmentService::assignCollection: [".count($workers)."] workers selected");
        
        if (count($workers) == 0) {
            $this->logger->err("WorkerTaskAssignmentService::assignCollection: Cannot assign, no active workers.");
            return self::ASSIGN_COLLECTION_NO_WORKERS_STATUS_CODE;
        }    
        
        $this->logger->info("WorkerTaskAssignmentService::assignCollection: Task collection count [".count($tasks)."]");

        shuffle($workers);
        foreach ($workers as $worker) {            
            if ($this->assignCollectionToWorker($tasks, $worker)) {
                foreach ($tasks as $task) {
                    $this->taskService->setStarted(
                        $task,
                        $worker,
                        $task->getRemoteId()
                    );

                    $this->taskService->getEntityManager()->persist($task);
                }

                $this->taskService->getEntityManager()->flush();

                return self::ASSIGN_COLLECTION_OK_STATUS_CODE;
            }     
        }
        
        return self::ASSIGN_COLLECTION_COULD_NOT_ASSIGN_TO_ANY_WORKERS_STATUS_CODE;          
     }    
    
    
    /**
     *
     * @param Task $task
     * @param Worker $worker
     * @return int|boolean
     */
    private function assignToWorker(Task $task, Worker $worker) {
        $this->logger->info("WorkerTaskAssignmentService::assignToWorker: Trying worker with id [".$worker->getId()."] at host [".$worker->getHostname()."]");                    
        
        $postFields = array(
            'url' => $task->getUrl(),
            'type' => (string)$task->getType()            
        );
        
        if ($task->hasParameters()) {
            $postFields['parameters'] = $task->getParameters();
        }
        
        $requestUrl = $this->urlService->prepare('http://' . $worker->getHostname() . '/task/create/');               
        $httpRequest = $this->httpClientService->postRequest($requestUrl, null, $postFields);        
        
        try {
            $response = $httpRequest->send();
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {
            $this->logger->info("WorkerTaskAssignmentService::assignToWorker: " . $requestUrl . ": " . $curlException->getErrorNo().' '.$curlException->getError());
            return false;
        } catch (\Guzzle\Http\Exception\BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();
        }
        
        $this->logger->info("WorkerTaskAssignmentService::assignToWorker " . $requestUrl . ": " . $response->getStatusCode()." ".$response->getReasonPhrase());     
        if (!$response->isSuccessful()) {
            return false;
        }
        
        return json_decode($response->getBody())->id;       
    }
    
    
    /**
     *
     * @param array $task
     * @param Worker $worker
     * @return int|boolean
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
     * @param type $remoteCollection
     */
    private function setTaskRemoteIdFromRemoteCollection(Task $task, $remoteCollection) {
        foreach ($remoteCollection as $taskObject) {
            if ($task->getUrl() == $taskObject->url && (string)$task->getType() == $taskObject->type) {
                $task->setRemoteId($taskObject->id);
            }
        }
    }
}