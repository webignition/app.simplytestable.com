<?php

namespace Application\Migrations;

use SimplyTestable\ApiBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\ApiBundle\Entity\State;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120724153738_add_task_cancelled_state extends EntityModificationMigration
{
    public function postUp(Schema $schema)
    {        
        $state_completed = new State();
        $state_completed->setName('task-cancelled');        
        $this->getEntityManager()->persist($state_completed);
        $this->getEntityManager()->flush();      
    }

    public function postDown(Schema $schema)
    {
        $stateNames = array(
            'task-cancelled'
        );
        
        foreach ($stateNames as $stateName) {
            $state = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->findOneByName($stateName);
            $this->getEntityManager()->remove($state);
            $this->getEntityManager()->flush();
        }
    }
}
