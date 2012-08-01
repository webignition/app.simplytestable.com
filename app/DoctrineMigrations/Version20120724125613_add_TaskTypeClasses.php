<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
 SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120724125613_add_TaskTypeClasses extends EntityModificationMigration
{
    private $taskTypeClasses = array(
        'verification' => 'For the verification of quality aspects such as HTML validity, CSS validity or the presence of a robot.txt file',
        'discovery' => 'For the discovery of information, such as collecting all unique URLs within a given page'
    );
    
    public function postUp(Schema $schema) {
        foreach ($this->taskTypeClasses as $name => $description) {
            $taskTypeClass = new TaskTypeClass();
            $taskTypeClass->setName($name);
            $taskTypeClass->setDescription($description);
            
            $this->getEntityManager()->persist($taskTypeClass);
            $this->getEntityManager()->flush();            
        }
    }
    
    public function postDown(Schema $schema) {
        foreach ($this->taskTypeClasses as $name => $description) {
            $taskTypeClass = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->findOneByName($name);
            $this->getEntityManager()->remove($taskTypeClass);
            $this->getEntityManager()->flush();
        }        
    }
}
