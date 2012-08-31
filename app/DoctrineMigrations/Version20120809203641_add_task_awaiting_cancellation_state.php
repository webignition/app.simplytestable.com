<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\ApiBundle\Entity\State;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120809203641_add_task_awaiting_cancellation_state extends EntityModificationMigration
{
    public function postUp(Schema $schema)
    {        
        $state_awaiting_cancellation = new State();
        $state_awaiting_cancellation->setName('task-awaiting-cancellation');        
        $this->getEntityManager()->persist($state_awaiting_cancellation);
        $this->getEntityManager()->flush();      
    }

    public function postDown(Schema $schema)
    {
        $stateNames = array(
            'task-awaiting-cancellation'
        );
        
        foreach ($stateNames as $stateName) {
            $state = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->findOneByName($stateName);
            $this->getEntityManager()->remove($state);
            $this->getEntityManager()->flush();
        }
    }
}
