<?php

namespace SimplyTestable\ApiBundle\Services;

use GuzzleHttp\Client as HttpClient;
use Psr\Log\LoggerInterface;

abstract class WorkerTaskService
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var StateService
     */
    protected $stateService;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var TaskService
     */
    protected $taskService;

    /**
     * @param LoggerInterface $logger
     * @param StateService $stateService
     * @param HttpClient $httpClient
     * @param TaskService $taskService
     */
    public function __construct(
        LoggerInterface $logger,
        StateService $stateService,
        HttpClient $httpClient,
        TaskService $taskService
    ) {
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->httpClient = $httpClient;
        $this->taskService = $taskService;
    }
}
