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
    
    /**
     * 
     * @return int
     */
    public function getSelectableCount() {
        $queryBuilder = $this->getEntityRepository()->createQueryBuilder('TaskType');

        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(TaskType.id) as task_type_total');
        $queryBuilder->where('TaskType.selectable = 1');
        
        $result = $queryBuilder->getQuery()->getResult();
        return (int)($result[0]['task_type_total']);        
    }
  
}