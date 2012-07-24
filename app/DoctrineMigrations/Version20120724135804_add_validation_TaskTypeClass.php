<?php

namespace Application\Migrations;

use SimplyTestable\ApiBundle\Migration\EntityModificationMigration,
    SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120724135804_add_validation_TaskTypeClass extends EntityModificationMigration
{
    private $taskTypeClasses = array(
        'validation' => 'For the validation of syntactial correctness, such as HTML or CSS validation'
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
