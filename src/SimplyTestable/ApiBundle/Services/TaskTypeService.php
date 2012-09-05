<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;


class TaskTypeService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Task\Type\Type';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }     
    
    
    /**
     *
     * @param string $taskTypeName
     * @return boolean
     */
    public function exists($taskTypeName) {
        return !is_null($this->getByName($taskTypeName));
    }    
    
    /**
     *
     * @param string $taskTypeName
     * @return TaskType
     */
    public function getByName($taskTypeName) {
        return $this->getEntityRepository()->findOneBy(array('name' => $taskTypeName));
    }    
  
}