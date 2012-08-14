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
     * @var \webignition\Http\Client\Client
     */
    private $httpClient;    
    
    /**
     *
     * @param EntityManager $entityManager
     * @param WorkerActivationRequestService $workerActivationRequestService
     * @param \webignition\Http\Client\Client $httpClient 
     */
    public function __construct(
            EntityManager $entityManager,
            WorkerActivationRequestService $workerActivationRequestService,
            \webignition\Http\Client\Client $httpClient
    ) {
        parent::__construct($entityManager);
        
        $this->workerActivationRequestService = $workerActivationRequestService;
        $this->httpClient = $httpClient;
        $this->httpClient->redirectHandler()->enable();
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
        
        $request = new \HttpRequest($activationVerificationUrl, HTTP_METH_POST);
        $request->setPostFields(array('token' => $token));
        
        return $this->httpClient->getResponse($request)->getResponseCode() == 200;
    }
    
    /**
     *
     * @param Worker $worker
     * @return Worker
     */
    public function persistAndFlush(Worker $worker) {
        $this->getEntityManager()->persist($worker);
        $this->getEntityManager()->flush();
        return $worker;
    }    
}