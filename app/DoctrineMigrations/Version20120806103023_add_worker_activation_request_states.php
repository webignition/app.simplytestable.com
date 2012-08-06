<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\ApiBundle\Entity\State;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120806103023_add_worker_activation_request_states extends EntityModificationMigration
{
    public function postUp(Schema $schema)
    {   
        
        $state_verified = new State();
        $state_verified->setName('worker-activation-request-verified');       
        $this->getEntityManager()->persist($state_verified);
        $this->getEntityManager()->flush();

        $state_failed = new State();
        $state_failed->setName('worker-activation-request-failed');       
        $this->getEntityManager()->persist($state_failed);
        $this->getEntityManager()->flush();         
        
        $state_new = new State();
        $state_new->setName('worker-activation-request-awaiting-verification');
        $state_new->setNextState($state_verified);        
        $this->getEntityManager()->persist($state_new);
        $this->getEntityManager()->flush();        
    }

    public function postDown(Schema $schema)
    {
        $stateNames = array(
            'worker-activation-request-awaiting-verification',
            'worker-activation-request-failed',
            'worker-activation-request-verified'
        );
        
        foreach ($stateNames as $stateName) {
            $state = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->findOneByName($stateName);
            $this->getEntityManager()->remove($state);
            $this->getEntityManager()->flush();
        }
    }
}
