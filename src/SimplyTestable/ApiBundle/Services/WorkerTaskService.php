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
     * @param StateService $stateService
     * @param HttpClientService $httpClientService
     * @param UrlService $urlService
     * @param TaskService $taskService
     */
    public function __construct(
        LoggerInterface $logger,
        StateService $stateService,
        HttpClientService $httpClientService,
        UrlService $urlService,
        TaskService $taskService
    ) {
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->httpClientService = $httpClientService;
        $this->urlService = $urlService;
        $this->taskService = $taskService;
    }
}
