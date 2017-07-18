<?php
namespace SimplyTestable\ApiBundle\Services;

use Psr\Log\LoggerInterface;

abstract class WorkerTaskService
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var WorkerService
     */
    protected $workerService;

    /**
     * @var StateService
     */
    protected $stateService;

    /**
     * @var HttpClientService
     */
    protected $httpClientService;

    /**
     * @var UrlService
     */
    protected $urlService;

    /**
     * @var TaskService
     */
    protected $taskService;

    /**
     * @param LoggerInterface $logger
     * @param WorkerService $workerService
     * @param StateService $stateService
     * @param HttpClientService $httpClientService
     * @param UrlService $urlService
     * @param TaskService $taskService
     */
    public function __construct(
        LoggerInterface $logger,
        WorkerService $workerService,
        StateService $stateService,
        HttpClientService $httpClientService,
        UrlService $urlService,
        TaskService $taskService
    ) {
        $this->logger = $logger;
        $this->workerService = $workerService;
        $this->stateService = $stateService;
        $this->httpClientService = $httpClientService;
        $this->urlService = $urlService;
        $this->taskService = $taskService;
    }
}
