<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\ApiBundle\Entity\State;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130514111323_rename_job_no_sitemap_state extends EntityModificationMigration
{
    private $oldStateName = 'job-no-sitemap';
    private $newStateName = 'job-failed-no-sitemap';
    
    public function postUp(Schema $schema)
    {
        $state = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->findOneByName($this->oldStateName);
        $state->setName($this->newStateName);
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->flush();
    }

    public function postDown(Schema $schema)
    {
        $state = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->findOneByName($this->newStateName);
        $state->setName($this->oldStateName);
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->flush();
    }
}
