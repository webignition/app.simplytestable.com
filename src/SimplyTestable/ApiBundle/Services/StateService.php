<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\State;


class StateService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\State';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }

    
    /**
     * @param string $name
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function fetch($name) {        
        if (!$this->has($name)) {
            $this->create($name);
        }

        return $this->find($name);
    }
    
    
    /**
     *
     * @param string $name
     * @return \SimplyTestable\ApiBundle\Entity\State 
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
    
    
    /**
     *
     * @param string $name
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function create($name) {
        $state = new State();
        $state->setName($name);
        
        $this->persistAndFlush($name);
        return $state;
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