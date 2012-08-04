<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;


class WorkerService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Worker';
    
    /**
     *
     * @var \webignition\Http\Client\Client
     */
    private $httpClient;
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \webignition\Http\Client\Client $httpClient 
     */
    public function __construct(EntityManager $entityManager, \webignition\Http\Client\Client $httpClient) {
        parent::__construct($entityManager);
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
     * @param State $job
     * @return State
     */
    public function persistAndFlush(State $state) {
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->flush();
        return $state;
    }    
}