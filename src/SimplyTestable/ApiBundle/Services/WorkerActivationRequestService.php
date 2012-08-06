<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use SimplyTestable\ApiBundle\Entity\State;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;


class WorkerActivationRequestService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\WorkerActivationRequest';   
    const STARTING_STATE = 'worker-activation-request-awaiting-verification';
    
    /**
     *
     * @var Logger
     */
    private $logger;    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StateService
     */
    private $stateService;    
    
    /**
     *
     * @var \webignition\Http\Client\Client
     */
    private $httpClient;      
    
    /**
     *
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     * @param \webignition\Http\Client\Client $httpClient 
     */
    public function __construct(
            EntityManager $entityManager,
            Logger $logger,
            \SimplyTestable\ApiBundle\Services\StateService $stateService,
            \webignition\Http\Client\Client $httpClient)
    {
        parent::__construct($entityManager);        
        
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->httpClient = $httpClient;
    }  
    
   
    
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     *
     * @param Worker $worker
     * @return SimplyTestable\ApiBundle\Entity\WorkerActivationRequest
     */
    public function get(Worker $worker) {       
        if (!$this->has($worker)) {
            $this->create($worker);
        }
        
        return $this->fetch($worker);     
    }
    
    
    /**
     *
     * @param WorkerActivationRequest $activationRequest
     * @return boolean
     */
    public function verify(WorkerActivationRequest $activationRequest) {
        $this->logger->info("WorkerActivationRequestService::verify: Initialising");
        
        $verifyUrl = 'http:// ' . $activationRequest->getWorker()->getHostname() . '/verify/';
        
        $httpRequest = new \HttpRequest($verifyUrl, HTTP_METH_POST);        
        
        $this->logger->info("WorkerActivationRequestService::verify: Requesting verification with " . $verifyUrl);

        $response = $this->httpClient->getResponse($httpRequest);

        $this->logger->info("WorkerActivationRequestService::verify: " . $verifyUrl . ": " . $response->getResponseCode()." ".$response->getResponseStatus());

        if ($response->getResponseCode() !== 200) {
            $this->logger->warn("WorkerActivationRequestService::verify: Activation request failed");
            return false;
        }

        return true;        
    }
    
    
    /**
     *
     * @param Worker $worker
     * @return boolean
     */
    private function has(Worker $worker) {
        return !is_null($this->fetch($worker));
    }
    
   
    /**
     *
     * @param Worker $worker
     * @return WorkerActivationRequest 
     */
    private function fetch(Worker $worker) {
        return $this->getEntityRepository()->findOneBy(
            array('worker' => $worker
        ));
    }
    
    
    /**
     *
     * @param Worker $worker
     * @return WorkerActivationRequest 
     */
    private function create(Worker $worker) {             
        $activationRequest = new WorkerActivationRequest();
        $activationRequest->setState($this->getStartingState());
        $activationRequest->setWorker($worker);
        
        return $this->persistAndFlush($activationRequest);
    }    

    
    /**
     *
     * @param WorkerActivationRequest $workerActivationRequest
     * @return WorkerActivationRequest
     */
    public function persistAndFlush(WorkerActivationRequest $workerActivationRequest) {
        $this->getEntityManager()->persist($workerActivationRequest);
        $this->getEntityManager()->flush();
        return $workerActivationRequest;
    }
    
    
    /**
     *
     * @return State
     */
    public function getStartingState() {
        return $this->stateService->fetch(self::STARTING_STATE);
    }
}