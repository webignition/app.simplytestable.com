<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;


class WorkerService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Worker';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    public function verify() {
        
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