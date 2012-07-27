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
     * @var \webignition\Http\Client\Client
     */
    private $stateService;
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     */
    public function __construct(EntityManager $entityManager, \SimplyTestable\ApiBundle\Services\StateService $stateService) {
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
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\StateService
     */
    public function getStateService() {
        return $this->stateService;                
    }
}