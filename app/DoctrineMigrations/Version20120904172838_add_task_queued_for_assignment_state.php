<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\ApiBundle\Entity\State;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120904172838_add_task_queued_for_assignment_state extends EntityModificationMigration
{
    public function postUp(Schema $schema)
    {      
        $state_queued = $this->container->get('simplytestable.services.taskservice')->getQueuedState();
        
        $state = new State();
        $state->setName('task-queued-for-assignment');        
        $state->setNextState($state_queued);
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->flush();      
    }

    public function postDown(Schema $schema)
    {
        $stateNames = array(
            'task-queued-for-assignment'
        );
        
        foreach ($stateNames as $stateName) {
            $state = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->findOneByName($stateName);
            $this->getEntityManager()->remove($state);
            $this->getEntityManager()->flush();
        }
    }
}
