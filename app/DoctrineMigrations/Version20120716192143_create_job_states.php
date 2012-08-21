<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\ApiBundle\Entity\State;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120716192143_create_job_states extends EntityModificationMigration
{
    public function postUp(Schema $schema)
    {
        $state_completed = new State();
        $state_completed->setName('job-completed');        
        $this->getEntityManager()->persist($state_completed);
        $this->getEntityManager()->flush();
        
        $state_in_progress = new State();
        $state_in_progress->setName('job-in-progress');
        $state_in_progress->setNextState($state_completed);        
        $this->getEntityManager()->persist($state_in_progress);
        $this->getEntityManager()->flush();  
        
        $state_queued = new State();
        $state_queued->setName('job-queued');
        $state_queued->setNextState($state_in_progress);        
        $this->getEntityManager()->persist($state_queued);
        $this->getEntityManager()->flush();                
        
        $state_preparing = new State();
        $state_preparing->setName('job-preparing');
        $state_preparing->setNextState($state_queued);        
        $this->getEntityManager()->persist($state_preparing);
        $this->getEntityManager()->flush();        

        $state_new = new State();
        $state_new->setName('job-new');
        $state_new->setNextState($state_preparing);        
        $this->getEntityManager()->persist($state_new);
        $this->getEntityManager()->flush();        
    }

    public function postDown(Schema $schema)
    {
        $stateNames = array(
            'job-new',
            'job-preparing',
            'job-queued',            
            'job-in-progress',
            'job-completed'
        );
        
        foreach ($stateNames as $stateName) {
            $state = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->findOneByName($stateName);
            $this->getEntityManager()->remove($state);
            $this->getEntityManager()->flush();
        }
    }
}
