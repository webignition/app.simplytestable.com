<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;
use SimplyTestable\ApiBundle\Entity\TimePeriod;


abstract class WorkerTaskService {
    
    /**
     *
     * @var Logger
     */
    protected $logger;  
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\WorkerService
     */
    protected $workerService;    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StateService
     */
    protected $stateService;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\HttpClientService
     */
    private $httpClientService;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\UrlService
     */
    protected $urlService; 
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\TaskService
     */    
    protected $taskService;
    
    /**
     *
     * @param Logger $logger
     * @param \SimplyTestable\ApiBundle\Services\WorkerService $workerService 
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     * @param \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService
     * @param \SimplyTestable\ApiBundle\Services\UrlService $urlService
     * @param \SimplyTestable\ApiBundle\Services\TaskService $taskService
     */
    public function __construct(
            Logger $logger,
            \SimplyTestable\ApiBundle\Services\WorkerService $workerService,
            \SimplyTestable\ApiBundle\Services\StateService $stateService,
            \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService,
            \SimplyTestable\ApiBundle\Services\UrlService $urlService,
            \SimplyTestable\ApiBundle\Services\TaskService $taskService)            
    {        
        $this->logger = $logger;
        $this->workerService = $workerService;
        $this->stateService = $stateService;
        $this->httpClientService = $httpClientService; 
        $this->urlService = $urlService;
        $this->taskService = $taskService;
    }
}