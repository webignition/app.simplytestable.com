<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerTaskAssignment;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;
use SimplyTestable\ApiBundle\Entity\TimePeriod;


abstract class WorkerTaskService extends EntityService {
    
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
     * @var \webignition\Http\Client\Client
     */
    protected $httpClient;  
    
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
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param \SimplyTestable\ApiBundle\Services\WorkerService $workerService 
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     * @param \webignition\Http\Client\Client $httpClient 
     * @param \SimplyTestable\ApiBundle\Services\UrlService $urlService
     * @param \SimplyTestable\ApiBundle\Services\TaskService $taskService
     */
    public function __construct(
            EntityManager $entityManager,
            Logger $logger,
            \SimplyTestable\ApiBundle\Services\WorkerService $workerService,
            \SimplyTestable\ApiBundle\Services\StateService $stateService,
            \webignition\Http\Client\Client $httpClient,
            \SimplyTestable\ApiBundle\Services\UrlService $urlService,
            \SimplyTestable\ApiBundle\Services\TaskService $taskService)            
    {
        parent::__construct($entityManager);        
        
        $this->logger = $logger;
        $this->workerService = $workerService;
        $this->stateService = $stateService;
        $this->httpClient = $httpClient;
        $this->urlService = $urlService;
        $this->taskService = $taskService;
    }
}