<?php
namespace SimplyTestable\ApiBundle\Services;

use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class WorkerTaskCancellationService extends WorkerTaskService
{
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
