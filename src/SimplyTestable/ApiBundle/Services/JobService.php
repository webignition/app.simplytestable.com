<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Job\Job;
//use SimplyTestable\ApiBundle\Entity\State;
//use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\StateService;

class JobService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\Job';    
    
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
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function getById($id) {
        return $this->getEntityRepository()->find($id);
    }
    
    
    /**
     *
     * @param int $id
     * @return boolean
     */
    public function has($id) {
        return !is_null($this->getById($id));
    }
}