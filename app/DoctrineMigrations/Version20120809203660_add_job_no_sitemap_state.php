<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\ApiBundle\Entity\State;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120809203660_add_job_no_sitemap_state extends EntityModificationMigration
{
    public function postUp(Schema $schema)
    {        
        $state_no_sitemap = new State();
        $state_no_sitemap->setName('job-no-sitemap');        
        $this->getEntityManager()->persist($state_no_sitemap);
        $this->getEntityManager()->flush();      
    }

    public function postDown(Schema $schema)
    {
        $stateNames = array(
            'job-no-sitemap'
        );
        
        foreach ($stateNames as $stateName) {
            $state = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->findOneByName($stateName);
            $this->getEntityManager()->remove($state);
            $this->getEntityManager()->flush();
        }
    }
}
