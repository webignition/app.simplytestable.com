<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\State;


class StateService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\State';
    
    private $states = array();
    
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
        if (!isset($this->states[$name])) {            
            if (!$this->has($name)) {
                $this->create($name);
            }            
            
            $this->states[$name] = $this->find($name);
        }        
        
        return $this->states[$name];
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
        $this->getManager()->persist($state);
        $this->getManager()->flush();
        return $state;
    }    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Repository\StateRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }
}