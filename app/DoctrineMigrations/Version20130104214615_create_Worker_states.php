<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\ApiBundle\Entity\State;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130104214615_create_Worker_states extends EntityModificationMigration
{
    public function postUp(Schema $schema)
    {
        $stateNames = array(
            'worker-active',
            'worker-deleted',
            'worker-offline',
            'worker-unactivated'
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
            'worker-active',
            'worker-deleted',
            'worker-offline',
            'worker-unactivated'
        );
        
        foreach ($stateNames as $stateName) {
            $state = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->findOneByName($stateName);
            $this->getEntityManager()->remove($state);
        }
        
        $this->getEntityManager()->flush();        
    }
}
