<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120724140105_update_verification_TaskTypeClass_description extends EntityModificationMigration
{
    const VERIFICATION_CLASS_NAME = 'verification';
    
    private $verificationDescriptions = array(
        'up' => 'For the verification of quality aspects such as the presence of a robots.txt file',
        'down' => 'For the verification of quality aspects such as HTML validity, CSS validity or the presence of a robot.txt file'
    );
    
    public function postUp(Schema $schema) {
        /* @var $taskTypeClass TaskTypeClass */
        $taskTypeClass = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->findOneByName(self::VERIFICATION_CLASS_NAME);
        $taskTypeClass->setDescription($this->verificationDescriptions['up']);
        
        $this->getEntityManager()->persist($taskTypeClass);
        $this->getEntityManager()->flush(); 
    }
    
    public function postDown(Schema $schema) {
        /* @var $taskTypeClass TaskTypeClass */
        $taskTypeClass = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->findOneByName(self::VERIFICATION_CLASS_NAME);
        $taskTypeClass->setDescription($this->verificationDescriptions['down']);
        
        $this->getEntityManager()->persist($taskTypeClass);
        $this->getEntityManager()->flush();        
    }
}
