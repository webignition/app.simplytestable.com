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
     * @var \SimplyTestable\ApiBundle\Services\HttpClientService
     */
    private $httpClientService;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Service\UrlService $urlService
     */
    private $urlService;    
    
    /**
     *
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     * @param \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService 
     * @param \SimplyTestable\ApiBundle\Services\UrlService $urlService
     */
    public function __construct(
            EntityManager $entityManager,
            Logger $logger,
            \SimplyTestable\ApiBundle\Services\StateService $stateService,
            \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService,
            \SimplyTestable\ApiBundle\Services\UrlService $urlService)            
    {
        parent::__construct($entityManager);        
        
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->httpClientService = $httpClientService; 
        $this->urlService = $urlService;
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
     * @param WorkerActivationRequest $activationRequest
     * @return boolean
     */
    public function verify(WorkerActivationRequest $activationRequest) {
        $this->logger->info("WorkerActivationRequestService::verify: Initialising");
        
        $requestUrl = $this->urlService->prepare('http://' . $activationRequest->getWorker()->getHostname() . '/verify/');        
        
        $httpRequest = $this->httpClientService->postRequest($requestUrl, null, array(
            'hostname' => $activationRequest->getWorker()->getHostname(),
            'token' => $activationRequest->getToken()
        ));

        $this->logger->info("WorkerActivationRequestService::verify: Requesting verification with " . $requestUrl);
        
        try {
            $response = $httpRequest->send();
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {
            $this->logger->info("WorkerActivationRequestService::verify" . $requestUrl . ": " . $curlException->getErrorNo().' '.$curlException->getError());
            return false;
        } catch (\Guzzle\Http\Exception\BadResponseException $badResponseException) {            
            $response = $badResponseException->getResponse();            
            $this->logger->info("WorkerActivationRequestService::verify " . $requestUrl . ": " . $badResponseException->getResponse()->getStatusCode().' '.$badResponseException->getResponse()->getReasonPhrase());
        } 

        $this->logger->info("WorkerActivationRequestService::verify: " . $requestUrl . ": " . $response->getStatusCode()." ".$response->getReasonPhrase());

        if ($response->getStatusCode() !== 200) {                
            if ($response->getStatusCode() === 503) {
                $this->logger->info("WorkerActivationRequestService::verify: Worker at ".$activationRequest->getWorker()->getHostname()." is in read-only mode");
            }

            $this->logger->err("WorkerActivationRequestService::verify: Activation request failed");

            return $response->getStatusCode();
        }

        $activationRequest->setNextState();
        $this->persistAndFlush($activationRequest);

        $worker = $activationRequest->getWorker();

        $worker->setState($this->stateService->fetch('worker-active'));

        $this->getEntityManager()->persist($worker);
        $this->getEntityManager()->flush();

        return 0;           
  
    }
    
    
    /**
     *
     * @param Worker $worker
     * @return boolean
     */
    public function has(Worker $worker) {
        return !is_null($this->fetch($worker));
    }
    
   
    /**
     *
     * @param Worker $worker
     * @return WorkerActivationRequest 
     */
    public function fetch(Worker $worker) {
        return $this->getEntityRepository()->findOneBy(
            array('worker' => $worker
        ));
    }
    
    
    /**
     *
     * @param Worker $worker
     * @param string $token
     * @return WorkerActivationRequest 
     */
    public function create(Worker $worker, $token) {             
        $activationRequest = new WorkerActivationRequest();
        $activationRequest->setState($this->getStartingState());
        $activationRequest->setWorker($worker);
        $activationRequest->setToken($token);
        
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