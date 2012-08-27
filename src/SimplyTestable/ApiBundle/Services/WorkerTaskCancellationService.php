<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;
use SimplyTestable\ApiBundle\Entity\TimePeriod;


class WorkerTaskCancellationService extends WorkerTaskService {
    
    const ENTITY_NAME = '';    
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    public function cancel(Task $task) {        
        $this->logger->info("WorkerTaskCancellationService::cancel: Initialising");
        $this->logger->info("WorkerTaskCancellationService::cancel: Processing task [".$task->getId()."] [".$task->getType()."] [".$task->getUrl()."]");
        
        if ($this->taskService->isCancelled($task)) {
            $this->logger->info("WorkerTaskCancellationService::cancel: Task is cancelled, nothing to do");
            return true;            
        }
        
        if ($this->taskService->isCompleted($task)) {
            $this->logger->info("WorkerTaskCancellationService::cancel: Task is completed, nothing to do");
            return true;            
        }
        
        $requestUrl = $this->urlService->prepare('http://' . $worker->getHostname() . '/task/cancel/');

        $httpRequest = new \HttpRequest($requestUrl, HTTP_METH_POST);
        $httpRequest->setPostFields(array(
            'id' => $task->getRemoteId()
        ));

        try {            
            $response = $this->httpClient->getResponse($httpRequest);
            $responseObject = json_decode($response->getBody());

            $this->logger->info("WorkerTaskCancellationService::cancel " . $requestUrl . ": " . $response->getResponseCode()." ".$response->getResponseStatus());
            
            return ($response->getResponseCode() === 200) ? $responseObject->id : false;
        } catch (CurlException $curlException) {
            $this->logger->info("WorkerTaskCancellationService::cancel: " . $requestUrl . ": " . $curlException->getMessage());
        }        
        
        return true;
    }    
}