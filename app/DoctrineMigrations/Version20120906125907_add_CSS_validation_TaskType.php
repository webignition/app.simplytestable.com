<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
 SimplyTestable\ApiBundle\Entity\Task\Type\Type,
 SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass,
 Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120906125907_add_CSS_validation_TaskType extends EntityModificationMigration
{
    private $taskTypes = array(
        'CSS validation' => array(
            'description' => 'Validates the CSS related to a given web document URL',
            'class' => 'verification'
        )
    );
    
    public function postUp(Schema $schema)
    {
        foreach ($this->taskTypes as $name => $properties) {
            $class = $taskTypeClass = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->findOneByName($properties['class']);
 
            $taskType = new Type();
            $taskType->setClass($class);
            $taskType->setDescription($properties['description']);
            $taskType->setName($name);
            
            $this->getEntityManager()->persist($taskType);
            $this->getEntityManager()->flush();            
        }
    }
    
    public function postDown(Schema $schema)
    {
        foreach ($this->taskTypes as $name => $properties) {            
            $taskType = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Type')->findOneByName($properties[$name]);
            $this->getEntityManager()->remove($taskType);
            $this->getEntityManager()->flush();
        }        
    }
}
