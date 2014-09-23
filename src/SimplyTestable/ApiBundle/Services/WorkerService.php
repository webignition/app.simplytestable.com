<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;


class WorkerService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Worker';
    
    /**
     *
     * @var WorkerActivationRequestSerice 
     */
    private $workerActivationRequestService;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\HttpClientService
     */
    private $httpClientService;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StateService
     */
    private $stateService;
    
    /**
     *
     * @param EntityManager $entityManager
     * @param WorkerActivationRequestService $workerActivationRequestService
     * @param \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService
     */
    public function __construct(
            EntityManager $entityManager,
            WorkerActivationRequestService $workerActivationRequestService,
            \SimplyTestable\ApiBundle\Services\HttpClientService $httpClientService,
            \SimplyTestable\ApiBundle\Services\StateService $stateService
    ) {
        parent::__construct($entityManager);
        
        $this->workerActivationRequestService = $workerActivationRequestService;
        $this->httpClientService = $httpClientService; 
        $this->stateService = $stateService;
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
     * @param int $id
     * @return \SimplyTestable\ApiBundle\Entity\Worker
     */
    public function getById($id) {
        return $this->getEntityRepository()->find($id);
    }
    
    
    /**
     *
     * @return Worker
     */
    public function get($hostname) {
        if (!$this->has($hostname)) {
            $this->create($hostname);
        }
        
        return $this->fetch($hostname);
    }
    
    /**
     *
     * @return boolean
     */
    private function has($hostname) {
        return !is_null($this->fetch($hostname));
    }
    
    /**
     *
     * @return Worker 
     */
    public function fetch($hostname) {
        return $this->getEntityRepository()->findOneByHostname($hostname);
    }
    
    
    /**
     *
     * @return Worker
     */
    private function create($hostname) {        
        $worker = new Worker();
        $worker->setHostname($hostname);        
        return $this->persistAndFlush($worker);        
    }
    
    
    /**
     *
     * @param string $hostname
     * @param string $token
     * @return boolean
     */
    public function verify($hostname, $token) {
        $activationVerificationUrl = 'http://' . $hostname . '/activate/verify/';
        
        $request = new \HttpRequest($activationVerificationUrl, \Guzzle\Http\Message\Request::POST);
        $request->setPostFields(array('token' => $token));
        
        return $this->httpClient->getResponse($request)->getResponseCode() == 200;
    }
    
    /**
     *
     * @param Worker $worker
     * @return Worker
     */
    public function persistAndFlush(Worker $worker) {
        $this->getManager()->persist($worker);
        $this->getManager()->flush();
        return $worker;
    }  
    
    
    /**
     * 
     * @return int
     */
    public function count() {
        $queryBuilder = $this->getEntityRepository()->createQueryBuilder('Worker');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(DISTINCT Worker.id) as worker_total');
        
        $result = $queryBuilder->getQuery()->getResult();
        return (int)($result[0]['worker_total']);  
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Worker $worker
     * @return boolean
     */
    public function isActive(Worker $worker) {
        return $worker->getState()->equals($this->stateService->fetch('worker-active'));
    }
    
    
    /**
     * Get collection of active workers
     * 
     * @return array
     */
    public function getActiveCollection() {
        $workers = $this->getEntityRepository()->findAll();
        $selectedWorkers = array();        
        
        foreach ($workers as $worker) {
            if ($this->isActive($worker)) {
                $selectedWorkers[] = $worker;
            }            
        }
        
        return $selectedWorkers;
    }     
}