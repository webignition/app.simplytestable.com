<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\State;


class AccountPlanService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Account\Plan\Plan';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     *
     * @param string $name
     * @return \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan 
     */
    public function find($name) {
        return $this->getEntityRepository()->findOneByName($name);
    }
    
    
    /**
     *
     * @param string $name
     * @return boolean
     */
    public function has($name) {
        return !is_null($this->find($name));
    }  
}