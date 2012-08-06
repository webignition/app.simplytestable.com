<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use SimplyTestable\ApiBundle\Entity\State;


class WorkerActivationRequestService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\WorkerActivationRequest';   
    const STARTING_STATE = 'worker-activation-request-awaiting-verification';
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StateService
     */
    private $stateService;    
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     */
    public function __construct(
            EntityManager $entityManager,
            \SimplyTestable\ApiBundle\Services\StateService $stateService)
    {
        parent::__construct($entityManager);        
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