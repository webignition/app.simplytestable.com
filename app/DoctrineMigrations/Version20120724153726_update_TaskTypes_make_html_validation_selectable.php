<?php

namespace Application\Migrations;

use SimplyTestable\ApiBundle\Migration\EntityModificationMigration,
 SimplyTestable\ApiBundle\Entity\Task\Type\Type,
 SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass,
 Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120724153726_update_TaskTypes_make_html_validation_selectable extends EntityModificationMigration
{
    const HTML_VALIDATION_TASK_TYPE_NAME = 'HTML validation';
    
    
    public function postUp(Schema $schema)
    {
        /* @var $taskType Type */
        $taskType = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Type')->findOneByName(self::HTML_VALIDATION_TASK_TYPE_NAME);
        $taskType->setSelectable(true);
        $this->getEntityManager()->persist($taskType);
        $this->getEntityManager()->flush();         
    }
    
    public function postDown(Schema $schema)
    {
        /* @var $taskType Type */
        $taskType = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Type')->findOneByName(self::HTML_VALIDATION_TASK_TYPE_NAME);
        $taskType->setSelectable(false);
        $this->getEntityManager()->persist($taskType);
        $this->getEntityManager()->flush();        
    }
}
