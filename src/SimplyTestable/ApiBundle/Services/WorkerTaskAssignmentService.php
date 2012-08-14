<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerTaskAssignment;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;
use SimplyTestable\ApiBundle\Entity\TimePeriod;


class WorkerTaskAssignmentService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\WorkerTaskAssignment';   
    const STARTING_STATE = 'worker-activation-request-awaiting-verification';
    
    /**
     *
     * @var Logger
     */
    private $logger;  
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\WorkerService
     */
    private $workerService;    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StateService
     */
    private $stateService;
    
    /**
     *
     * @var \webignition\Http\Client\Client
     */
    private $httpClient;  
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\UrlService
     */
    private $urlService; 
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\TaskService
     */    
    private $taskService;
    
    /**
     *
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param \SimplyTestable\ApiBundle\Services\WorkerService $workerService 
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     * @param \webignition\Http\Client\Client $httpClient 
     * @param \SimplyTestable\ApiBundle\Services\UrlService $urlService
     * @param \SimplyTestable\ApiBundle\Services\TaskService $taskService
     */
    public function __construct(
            EntityManager $entityManager,
            Logger $logger,
            \SimplyTestable\ApiBundle\Services\WorkerService $workerService,
            \SimplyTestable\ApiBundle\Services\StateService $stateService,
            \webignition\Http\Client\Client $httpClient,
            \SimplyTestable\ApiBundle\Services\UrlService $urlService,
            \SimplyTestable\ApiBundle\Services\TaskService $taskService)            
    {
        parent::__construct($entityManager);        
        
        $this->logger = $logger;
        $this->workerService = $workerService;
        $this->stateService = $stateService;
        $this->httpClient = $httpClient;
        $this->urlService = $urlService;
        $this->taskService = $taskService;
    }  
    
    
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
     * @return WorkerTaskAssignent|boolean
     */
    public function assign(Task $task) {
        $this->logger->info("WorkerTaskAssignmentService::assign: Initialising");
        $this->logger->info("WorkerTaskAssignmentService::assign: Processing task [".$task->getId()."] [".$task->getType()."] [".$task->getUrl()."]");
        
        if (!$task->getState()->equals($this->taskService->getQueuedState())) {
            $this->logger->info("WorkerTaskAssignmentService::assign: Task is not queued, nothing to do.");
            return true;
        }
        
        $workerSelection = $this->getWorkerSelection();
        
        $this->logger->info("WorkerTaskAssignmentService::assign: [".count($workerSelection)."] workers selected");
        
        foreach ($workerSelection as $workerIndex => $worker) {            
            $remoteTaskId = $this->assignToWorker($task, $worker);
            if (is_int($remoteTaskId)) {
                $this->taskService->setStarted(
                    $task,
                    $worker,
                    $remoteTaskId
                );
                
                $this->logger->info("WorkerTaskAssignmentService::assign: Succeeded with worker with id [".$worker->getId()."] at host [".$worker->getHostname()."]");                    
                
                return $this->update($worker, $task);  
            }         
        }
        
        return false;        
    }
    
    
    
    /**
     *
     * @param Worker $worker
     * @param Task $task
     * @return WorkerTaskAssignment
     */
    private function update(Worker $worker, Task $task) {
        if ($this->has($worker)) {
            $workerTaskAssignment = $this->fetch($worker);
        } else {
            $workerTaskAssignment = new WorkerTaskAssignment();
            $workerTaskAssignment->setWorker($worker);
        }
        
        $workerTaskAssignment->setTask($task);
        $workerTaskAssignment->setDateTime(new \DateTime());
        
        return $this->persistAndFlush($workerTaskAssignment);
    }
    
    
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
        $httpRequest->setPostFields(array(
            'url' => $task->getUrl(),
            'type' => (string)$task->getType()
        ));

        try {            
            $response = $this->httpClient->getResponse($httpRequest);
            $responseObject = json_decode($response->getBody());

            $this->logger->info("WorkerTaskAssignmentService::assignToWorker " . $requestUrl . ": " . $response->getResponseCode()." ".$response->getResponseStatus());
            $this->logger->info("WorkerTaskAssignmentService::assignToWorker: remoteId [".$responseObject->id."]");
            
            return ($response->getResponseCode() === 200) ? $responseObject->id : false;
        } catch (CurlException $curlException) {
            $this->logger->info("WorkerTaskAssignmentService::assignToWorker: " . $requestUrl . ": " . $curlException->getMessage());
        }         
    }
    
    
    /**
     * Get a collection of workers to which a task could be assigned, in order
     * of preference
     * 
     * @return array  
     */
    private function getWorkerSelection() {
        $workerTaskAssignments = $this->getEntityRepository()->findAllOrderedByDateTime();
        if (count($workerTaskAssignments) === 0) {
            return $this->workerService->getEntityRepository()->findAll();
        }
        
        $selectedWorkers = $this->getWorkersNeverAssignedTasks();
        if (count($selectedWorkers) > 0) {
            return $selectedWorkers;
        }
        
        foreach ($workerTaskAssignments as $workerTaskAssignment) {
            /* @var $workerTaskAssignment WorkerTaskAssignment */
            $selectedWorkers[] = $workerTaskAssignment->getWorker();
        }
        
        return $selectedWorkers;
    } 
    
    
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