<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;

class TaskService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Task\Task';
    const STARTING_STATE = 'task-new';
    const CANCELLED_STATE = 'task-cancelled';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
}