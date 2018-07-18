<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use AppBundle\Entity\Worker;
use AppBundle\Entity\Task\Task;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class WorkerTaskAssignmentService extends WorkerTaskService
{
    const ASSIGN_COLLECTION_OK_STATUS_CODE = 0;
    const ASSIGN_COLLECTION_NO_WORKERS_STATUS_CODE = 1;
    const ASSIGN_COLLECTION_COULD_NOT_ASSIGN_TO_ANY_WORKERS_STATUS_CODE = 2;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param LoggerInterface $logger
     * @param StateService $stateService
     * @param HttpClient $httpClient
     * @param TaskService $taskService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        LoggerInterface $logger,
        StateService $stateService,
        HttpClient $httpClient,
        TaskService $taskService,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($logger, $stateService, $httpClient, $taskService);

        $this->entityManager = $entityManager;
    }

    /**
     * @param Task[] $tasks
     * @param Worker[] $workers
     *
     * @return int
     */
    public function assignCollection($tasks, $workers)
    {
        $this->logger->info("WorkerTaskAssignmentService::assignCollection: Initialising");
        $this->logger->info("WorkerTaskAssignmentService::assignCollection: [".count($workers)."] workers selected");

        $workerNames = [];
        foreach ($workers as $worker) {
            $workerNames[] = $worker->getHostname();
        }

        $this->logger->info(
            "WorkerTaskAssignmentService::assignCollection: workers [" . implode(',', $workerNames) . "]"
        );

        if (empty($workers)) {
            $this->logger->error("WorkerTaskAssignmentService::assignCollection: Cannot assign, no active workers.");

            return self::ASSIGN_COLLECTION_NO_WORKERS_STATUS_CODE;
        }

        $this->logger->info("WorkerTaskAssignmentService::assignCollection: Task collection count [".count($tasks)."]");
        if (empty($tasks)) {
            return self::ASSIGN_COLLECTION_OK_STATUS_CODE;
        }

        $taskGroups = $this->getTaskGroups($tasks, count($workers));

        $failedWorkers = [];
        $failedGroups = [];

        /* @var Task[] $tasks */
        foreach ($taskGroups as $workerIndex => $tasks) {
            $worker = $workers[$workerIndex];

            if ($this->assignCollectionToWorker($tasks, $worker)) {
                foreach ($tasks as $task) {
                    $this->taskService->setStarted(
                        $task,
                        $worker,
                        $task->getRemoteId()
                    );

                    $this->entityManager->persist($task);
                }

                $this->entityManager->flush();
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

            if (!empty($failedTasks)) {
                $taskQueuedState = $this->stateService->get(Task::STATE_QUEUED);

                foreach ($failedTasks as $task) {
                    $task->setState($taskQueuedState);

                    $this->entityManager->persist($task);
                    $this->entityManager->flush();
                }
            }

            return self::ASSIGN_COLLECTION_COULD_NOT_ASSIGN_TO_ANY_WORKERS_STATUS_CODE;
        }

        return self::ASSIGN_COLLECTION_OK_STATUS_CODE;
    }


    /**
     * @param Task[] $tasks
     * @param int $workerCount
     *
     * @return Task[]
     */
    private function getTaskGroups($tasks, $workerCount)
    {
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
     *
     * @return bool
     */
    private function assignCollectionToWorker($tasks, Worker $worker)
    {
        $this->logger->info(sprintf(
            'WorkerTaskAssignmentService::assignCollectionToWorker: Trying worker with id [%s] at host [%s]',
            $worker->getId(),
            $worker->getHostname()
        ));

        $requestData = array();
        foreach ($tasks as $task) {
            /* @var $task Task */
            $postFields = array(
                'url' => $task->getUrl(),
                'type' => (string)$task->getType()
            );

            $taskParameters = $task->getParametersString();

            if (!empty($taskParameters)) {
                $postFields['parameters'] = $taskParameters;
            }

            $requestData[] = $postFields;
        }

        $requestUrl = 'http://' . $worker->getHostname() . '/task/create/collection/';
        $httpRequest = new Request(
            'POST',
            $requestUrl,
            ['content-type' => 'application/x-www-form-urlencoded'],
            http_build_query(['tasks' => $requestData], '', '&')
        );

        try {
            $response = $this->httpClient->send($httpRequest);
        } catch (ConnectException $connectException) {
            $curlException = GuzzleCurlExceptionFactory::fromConnectException($connectException);

            $this->logger->error(sprintf(
                'WorkerTaskAssignmentService::assignCollectionToWorker: %s: %s %s',
                $requestUrl,
                $curlException->getCurlCode(),
                $curlException->getMessage()
            ));

            return false;
        } catch (BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();
        }

        if (200 !== $response->getStatusCode()) {
            $this->logger->error(sprintf(
                'WorkerTaskAssignmentService::assignCollectionToWorker %s: %s %s',
                $requestUrl,
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));

            return false;
        }

        $this->logger->info(sprintf(
            'WorkerTaskAssignmentService::assignCollectionToWorker %s: %s %s',
            $requestUrl,
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));

        $responseObject = json_decode($response->getBody());
        foreach ($tasks as $task) {
            $this->setTaskRemoteIdFromRemoteCollection($task, $responseObject);
        }

        return true;
    }

    /**
     * @param Task $task
     *
     * @param \stdClass $remoteCollection
     */
    private function setTaskRemoteIdFromRemoteCollection(Task $task, $remoteCollection)
    {
        foreach ($remoteCollection as $taskObject) {
            if ($task->getUrl() == $taskObject->url && (string)$task->getType() == $taskObject->type) {
                $task->setRemoteId($taskObject->id);
            }
        }
    }
}
