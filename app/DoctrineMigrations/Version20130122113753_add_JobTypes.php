<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
 SimplyTestable\ApiBundle\Entity\Job\Type, 
 Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130122113753_add_JobTypes extends EntityModificationMigration
{
    private $jobTypes = array(
        'Full site' => array(
            'description' => 'Test the entirety of the site'
        ),
        'Single URL' => array(
            'description' => 'Test only the submitted URL'
        )        
    );
    
    public function postUp(Schema $schema)
    {
        foreach ($this->jobTypes as $name => $properties) {            
            $jobType = new Type();
            $jobType->setDescription($properties['description']);
            $jobType->setName($name);
            
            $this->getEntityManager()->persist($jobType);
            $this->getEntityManager()->flush();            
        }
    }
    
    public function postDown(Schema $schema)
    {
        foreach ($this->jobTypes as $name => $properties) {            
            $jobType = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\Type')->findOneByName($properties[$name]);
            $this->getEntityManager()->remove($jobType);
            $this->getEntityManager()->flush();
        }        
    }
}
