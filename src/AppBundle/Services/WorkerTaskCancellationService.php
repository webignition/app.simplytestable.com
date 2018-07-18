<?php

namespace AppBundle\Services;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use AppBundle\Entity\Task\Task;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class WorkerTaskCancellationService extends WorkerTaskService
{
    /**
     * @param Task[] $tasks
     *
     * @return bool
     */
    public function cancelCollection($tasks)
    {
        $remoteTaskIds = [];
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

        $requestUrl = 'http://' . $tasks[0]->getWorker()->getHostname() . '/task/cancel/collection/';
        $httpRequest = new Request(
            'POST',
            $requestUrl,
            ['content-type' => 'application/x-www-form-urlencoded'],
            http_build_query(['ids' => $remoteTaskIdsString], '', '&')
        );

        foreach ($tasks as $task) {
            $this->taskService->cancel($task);
        }

        try {
            $this->httpClient->send($httpRequest);
        } catch (ConnectException $connectException) {
            $curlException = GuzzleCurlExceptionFactory::fromConnectException($connectException);

            $this->logger->info(sprintf(
                'WorkerTaskCancellationService::cancelCollection::CurlException %s: %s %s',
                $requestUrl,
                $curlException->getCurlCode(),
                $curlException->getMessage()
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
