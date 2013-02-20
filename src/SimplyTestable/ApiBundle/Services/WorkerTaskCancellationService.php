<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use webignition\Http\Client\CurlException;


class WorkerTaskCancellationService extends WorkerTaskService {
    
    const ENTITY_NAME = '';    
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @return int
     */
    public function cancel(Task $task) {        
        $this->logger->info("WorkerTaskCancellationService::cancel: Initialising");
        $this->logger->info("WorkerTaskCancellationService::cancel: Processing task [".$task->getId()."] [".$task->getRemoteId()."] [".$task->getType()."] [".$task->getUrl()."]");
        
        if (!$this->taskService->isCancellable($task)) {
            $this->logger->err("WorkerTaskCancellationService::cancel: Task not in cancellable state [".$task->getState()->getName()."]");
            return -1;            
        }
        
        $requestUrl = $this->urlService->prepare('http://' . $task->getWorker()->getHostname() . '/task/cancel/');

        $httpRequest = new \HttpRequest($requestUrl, HTTP_METH_POST);
        $httpRequest->setPostFields(array(
            'id' => $task->getRemoteId()
        ));
        
        if ($this->httpClient instanceof \webignition\Http\Mock\Client\Client) {
            $this->logger->info("WorkerTaskCancellationService::cancel: response fixture path: " . $this->httpClient->getStoredResponseList()->getRequestFixturePath($httpRequest));
            if (file_exists($this->httpClient->getStoredResponseList()->getRequestFixturePath($httpRequest))) {
                $this->logger->info("WorkerTaskCancellationService::cancel: response fixture path: found");
            } else {
                $this->logger->info("WorkerTaskCancellationService::cancel: response fixture path: not found");
            }
        }         

        try {            
            $response = $this->httpClient->getResponse($httpRequest);       
            
            if ($response->getResponseCode() !== 200) {
                $this->logger->err("WorkerTaskCancellationService::cancel " . $requestUrl . ": " . $response->getResponseCode()." ".$response->getResponseStatus());
            }
            
            $this->taskService->cancel($task);
            
            return $response->getResponseCode();
        } catch (CurlException $curlException) {
            $this->logger->err("WorkerTaskCancellationService::cancel: " . $requestUrl . ": " . $curlException->getMessage());
            return $curlException->getCode();
        }
    }  
    
    
    public function cancelCollection($tasks) {
        $remoteTaskIds = array();
        foreach ($tasks as $task) {
            /* @var Task $task */
            $remoteTaskIds[] = $task->getRemoteId();
        }
        
        $remoteTaskIdsString = implode(',', $remoteTaskIds);
        
        $this->logger->info("WorkerTaskCancellationService::cancelCollection: Initialising");
        $this->logger->info("WorkerTaskCancellationService::cancelCollection: Processing remote IDs [".$remoteTaskIdsString."]");        
        
        $requestUrl = $this->urlService->prepare('http://' . $tasks[0]->getWorker()->getHostname() . '/task/cancel/collection/');

        $httpRequest = new \HttpRequest($requestUrl, HTTP_METH_POST);
        $httpRequest->setPostFields(array(
            'ids' => $remoteTaskIdsString
        ));

        try {            
            $response = $this->httpClient->getResponse($httpRequest);
            $responseObject = json_decode($response->getBody());

            $this->logger->info("WorkerTaskCancellationService::cancelCollection " . $requestUrl . ": " . $response->getResponseCode()." ".$response->getResponseStatus());
            
            foreach ($tasks as $task) {
                $this->taskService->cancel($task);
            }
            
            return ($response->getResponseCode() === 200) ? $responseObject->id : false;
        } catch (CurlException $curlException) {
            $this->logger->info("WorkerTaskCancellationService::cancelCollection: " . $requestUrl . ": " . $curlException->getMessage());
        }        
        
        return true;        
    }
}