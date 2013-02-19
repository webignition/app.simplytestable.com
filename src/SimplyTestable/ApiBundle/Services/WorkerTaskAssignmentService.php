<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerTaskAssignment;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;
use SimplyTestable\ApiBundle\Entity\TimePeriod;


class WorkerTaskAssignmentService extends WorkerTaskService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\WorkerTaskAssignment';    
    const ASSIGN_COLLECTION_OK_STATUS_CODE = 0;
    const ASSIGN_COLLECTION_NO_WORKERS_STATUS_CODE = 1;
    const ASSIGN_COLLECTION_COULD_NOT_ASSIGN_TO_ANY_WORKERS_STATUS_CODE = 2;
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     * Assign a task to a to-be-chosen worker.
     * Returns a WorkerTaskAssignment object on success or false on failure.
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
                
                $this->logger->info("WorkerTaskAssignmentService::assign: Succeeded with worker with id [".$worker->getId()."] at host [".$worker->getHostname()."]");                    
                
                //$this->update($worker, $task);  
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
            $response = $this->assignCollectionToWorker($tasks, $worker);

            if ($response === true) {
                foreach ($tasks as $task) {
                    $this->taskService->setStarted(
                        $task,
                        $worker,
                        $task->getRemoteId()
                    );                    

                    $this->getEntityManager()->persist($task);
                }

                //$this->update($worker, $task);
                $this->getEntityManager()->flush();

                return self::ASSIGN_COLLECTION_OK_STATUS_CODE;
            }     
        }
        
        return self::ASSIGN_COLLECTION_COULD_NOT_ASSIGN_TO_ANY_WORKERS_STATUS_CODE;          
     }
     
     
     /**
      * 
      * @param array $tasks
      * @return array
      */
     private function getQueuedTasksFromTaskCollection($tasks) {
        foreach ($tasks as $index => $task) {
            /* @var $task Task */
            if (!$this->taskService->isQueued($task) && !$this->taskService->isQueuedForAssignment($task)) {
                unset($tasks[$index]);
            }
        } 
        
        return $tasks;
     }
    
    
    /**
     *
     * @param Worker $worker
     * @param Task $task
     * @return WorkerTaskAssignment
     */
//    private function update(Worker $worker, Task $task) {
//        if ($this->has($worker)) {
//            $workerTaskAssignment = $this->fetch($worker);
//        } else {
//            $workerTaskAssignment = new WorkerTaskAssignment();
//            $workerTaskAssignment->setWorker($worker);
//        }
//        
//        $workerTaskAssignment->setTask($task);
//        $workerTaskAssignment->setDateTime(new \DateTime());
//
//        $this->persistAndFlush($workerTaskAssignment);        
//        return $workerTaskAssignment;
//    }
    
    
    /**
     *
     * @param Worker $worker
     * @return boolean
     */
    private function has(Worker $worker) {
        return !is_null($this->fetch($worker));
    }
    
    
    /**
     *
     * @param Worker $worker
     * @return WorkerTaskAssignment
     */
    private function fetch(Worker $worker) {
        return $this->getEntityRepository()->findOneBy(array(
            'worker' => $worker
        ));        
    }
    
    
    
    /**
     *
     * @param Task $task
     * @param Worker $worker
     * @return int|boolean
     */
    private function assignToWorker(Task $task, Worker $worker) {
        $this->logger->info("WorkerTaskAssignmentService::assignToWorker: Trying worker with id [".$worker->getId()."] at host [".$worker->getHostname()."]");                    
        
        $requestUrl = $this->urlService->prepare('http://' . $worker->getHostname() . '/task/create/');

        $httpRequest = new \HttpRequest($requestUrl, HTTP_METH_POST);
        
        $postFields = array(
            'url' => $task->getUrl(),
            'type' => (string)$task->getType()            
        );
        
        if ($task->hasParameters()) {
            $postFields['parameters'] = $task->getParameters();
        }
        
        $httpRequest->setPostFields($postFields);

        try {
            if ($this->httpClient instanceof \webignition\Http\Mock\Client\Client) {
                $this->logger->info("WorkerTaskAssignmentService::assignToWorker: response fixture path: " . $this->httpClient->getStoredResponseList()->getRequestFixturePath($httpRequest));
                if (file_exists($this->httpClient->getStoredResponseList()->getRequestFixturePath($httpRequest))) {
                    $this->logger->info("WorkerTaskAssignmentService::assignToWorker: response fixture path: found");
                } else {
                    $this->logger->info("WorkerTaskAssignmentService::assignToWorker: response fixture path: not found");
                }
            }              
            
            $response = $this->httpClient->getResponse($httpRequest);
            $responseObject = json_decode($response->getBody());

            $this->logger->info("WorkerTaskAssignmentService::assignToWorker " . $requestUrl . ": " . $response->getResponseCode()." ".$response->getResponseStatus());
            
            return ($response->getResponseCode() === 200) ? $responseObject->id : false;
        } catch (CurlException $curlException) {
            $this->logger->info("WorkerTaskAssignmentService::assignToWorker: " . $requestUrl . ": " . $curlException->getMessage());
        }         
    }
    
    
    /**
     *
     * @param array $task
     * @param Worker $worker
     * @return int|boolean
     */
    private function assignCollectionToWorker($tasks, Worker $worker) {        
        $this->logger->info("WorkerTaskAssignmentService::assignCollectionToWorker: Trying worker with id [".$worker->getId()."] at host [".$worker->getHostname()."]");                    
        
        $requestUrl = $this->urlService->prepare('http://' . $worker->getHostname() . '/task/create/collection');        

        $httpRequest = new \HttpRequest($requestUrl, HTTP_METH_POST);        
        
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
        
        $httpRequest->setPostFields(array(
            'tasks' => $requestData            
        ));
        
        if ($this->httpClient instanceof \webignition\Http\Mock\Client\Client) {
            $this->logger->info("WorkerTaskAssignmentService::assignCollectionToWorker: response fixture path: " . $this->httpClient->getStoredResponseList()->getRequestFixturePath($httpRequest));
            if (file_exists($this->httpClient->getStoredResponseList()->getRequestFixturePath($httpRequest))) {
                $this->logger->info("WorkerTaskAssignmentService::assignCollectionToWorker: response fixture path: found");
            } else {
                $this->logger->info("WorkerTaskAssignmentService::assignCollectionToWorker: response fixture path: not found");
            }
        }        
        
        try {            
            $response = $this->httpClient->getResponse($httpRequest);            
            
            if ($response->getResponseCode() !== 200) {
                return false;
            }
            
            $responseObject = json_decode($response->getBody());            
            foreach ($tasks as $task) {
                $this->setTaskRemoteIdFromRemoteCollection($task, $responseObject);
            }            

            $this->logger->info("WorkerTaskAssignmentService::assignCollectionToWorker " . $requestUrl . ": " . $response->getResponseCode()." ".$response->getResponseStatus());     
            
            return true;
        } catch (CurlException $curlException) {            
            $this->logger->info("WorkerTaskAssignmentService::assignCollectionToWorker: " . $requestUrl . ": " . $curlException->getMessage());
        }
        
        return false;
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
    
    
//    /**
//     * Get a collection of workers to which a task could be assigned in no 
//     * particular order
//     * 
//     * @return array  
//     */
//    private function getWorkerSelection() {
//        $workers = $this->workerService->getEntityRepository()->findAll();
//        $selectedWorkers = array();        
//        
//        foreach ($workers as $worker) {
//            /* @var $workerTaskAssignment WorkerTaskAssignment */
//            if ($this->workerService->isActive($worker)) {
//                $selectedWorkers[] = $worker;
//            }            
//        }
//        
//        return $selectedWorkers;
//    } 
    
    
    private function getWorkersNeverAssignedTasks() {
        $workerTaskAssignments = $this->getEntityRepository()->findAllOrderedByDateTime();
        if (count($workerTaskAssignments) === 0) {
            return $this->workerService->getEntityRepository()->findAll();
        }
        
        $selectedWorkers = array();        
        $allWorkers = $this->workerService->getEntityRepository()->findAll();
        
        foreach ($allWorkers as $worker) {
            /* @var $worker Worker */
            if (!$this->hasWorkerEverBeenAssignedATask($worker)) {
                $selectedWorkers[] = $worker;
            }
        }
        
        return $selectedWorkers;        
    }
    
    
    /**
     *
     * @param Worker $worker
     * @return boolean 
     */
    private function hasWorkerEverBeenAssignedATask(Worker $worker) {
        $workerTaskAssignments = $this->getEntityRepository()->findAllOrderedByDateTime();
        foreach ($workerTaskAssignments as $workerTaskAssignment) {
            /* @var $workerTaskAssignment WorkerTaskAssignment */
            if ($workerTaskAssignment->getWorker()->equals($worker)) {
                return true;
            }
        }        
        
        return false;
    }

    
    /**
     *
     * @param WorkerTaskAssignment $workerTaskAssignment
     * @return WorkerTaskAssignment
     */
    public function persistAndFlush(WorkerTaskAssignment $workerTaskAssignment) {
        $this->getEntityManager()->persist($workerTaskAssignment);
        $this->getEntityManager()->flush();
        return $workerTaskAssignment;
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\WorkerTaskAssignmentRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }
}