<?php
namespace SimplyTestable\ApiBundle\Services;

use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class WorkerTaskCancellationService extends WorkerTaskService
{
    /**
     * @param Task $task
     *
     * @return int
     */
    public function cancel(Task $task)
    {
        $this->logger->info("WorkerTaskCancellationService::cancel: Initialising");
        $this->logger->info(sprintf(
            'WorkerTaskCancellationService::cancel: Processing task [%s] [%s] [%s] [%s]',
            $task->getId(),
            $task->getRemoteId(),
            $task->getType()->getName(),
            $task->getUrl()
        ));

        if (!$this->taskService->isCancellable($task)) {
            $this->logger->error(sprintf(
                'WorkerTaskCancellationService::cancel: Task not in cancellable state [%s]',
                $task->getState()->getName()
            ));

            return -1;
        }

        $requestUrl = $this->urlService->prepare('http://' . $task->getWorker()->getHostname() . '/task/cancel/');
        $httpRequest = $this->httpClientService->postRequest($requestUrl, null, array(
            'id' => $task->getRemoteId()
        ));

        try {
            $response = $httpRequest->send();
        } catch (CurlException $curlException) {
            $this->logger->info(sprintf(
                'WorkerTaskCancellationService::cancel::CurlException %s: %s %s',
                $requestUrl,
                $curlException->getErrorNo(),
                $curlException->getError()
            ));
            return false;
        } catch (BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();
            $this->logger->info(sprintf(
                'WorkerTaskCancellationService::cancel::BadResponseException %s: %s %s',
                $requestUrl,
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
        }

        $this->taskService->cancel($task);
        return $response->getStatusCode();
    }

    /**
     * @param Task[] $tasks
     *
     * @return bool
     */
    public function cancelCollection($tasks)
    {
        $remoteTaskIds = array();
        foreach ($tasks as $task) {
            /* @var Task $task */
            $remoteTaskIds[] = $task->getRemoteId();
        }

        $remoteTaskIdsString = implode(',', $remoteTaskIds);

        $this->logger->info("WorkerTaskCancellationService::cancelCollection: Initialising");
        $this->logger->info(sprintf(
            'WorkerTaskCancellationService::cancelCollection: Processing remote IDs [%s]',
            $remoteTaskIdsString
        ));

        $requestUrl = $this->urlService->prepare(
            'http://' . $tasks[0]->getWorker()->getHostname() . '/task/cancel/collection/'
        );

        $httpRequest = $this->httpClientService->postRequest($requestUrl, null, array(
            'ids' => $remoteTaskIdsString
        ));

        foreach ($tasks as $task) {
            $this->taskService->cancel($task);
        }

        try {
            $httpRequest->send();
        } catch (CurlException $curlException) {
            $this->logger->info(sprintf(
                'WorkerTaskCancellationService::cancelCollection::CurlException %s: %s %s',
                $requestUrl,
                $curlException->getErrorNo(),
                $curlException->getError()
            ));
            return false;
        } catch (BadResponseException $badResponseException) {
            $this->logger->info(sprintf(
                'WorkerTaskCancellationService::cancelCollection::BadResponseException %s: %s %s',
                $requestUrl,
                $badResponseException->getResponse()->getStatusCode(),
                $badResponseException->getResponse()->getReasonPhrase()
            ));
            return false;
        }

        return true;
    }
}
