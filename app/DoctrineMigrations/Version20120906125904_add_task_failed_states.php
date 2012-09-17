<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\ApiBundle\Entity\State;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120906125904_add_task_failed_states extends EntityModificationMigration
{
    public function postUp(Schema $schema)
    {
        $stateNames = array(
            'task-failed-no-retry-available',
            'task-failed-retry-available',
            'task-failed-retry-limit-reached'
        );
        
        foreach ($stateNames as $stateName) {
            $state = new State();
            $state->setName($stateName);
            $this->getEntityManager()->persist($state);              
        }
        
        $this->getEntityManager()->flush();
    }

    public function postDown(Schema $schema)
    {
        $stateNames = array(
            'task-failed-no-retry-available',
            'task-failed-retry-available',
            'task-failed-retry-limit-reached'
        );
        
        foreach ($stateNames as $stateName) {
            $state = $this->getEntityManager()->getRepository('SimplyTestable\WorkerBundle\Entity\State')->findOneByName($stateName);
            $this->getEntityManager()->remove($state);
            $this->getEntityManager()->flush();
        }
    }
}
