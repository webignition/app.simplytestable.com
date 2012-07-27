<?php

namespace Application\Migrations;

use SimplyTestable\ApiBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120716214712_create_TaskType extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE TaskType (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                tasktypeclass_id INT NOT NULL,
                description LONGTEXT NOT NULL,
                selectable TINYINT(1) NOT NULL,
                UNIQUE INDEX UNIQ_F7737B3C5E237E06 (name),
                INDEX IDX_F7737B3CAEA19A54 (tasktypeclass_id),
                PRIMARY KEY(id)) ENGINE = InnoDB",
            "ALTER TABLE TaskType ADD CONSTRAINT FK_F7737B3CAEA19A54 FOREIGN KEY (tasktypeclass_id) REFERENCES TaskTypeClass (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE TaskType (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                tasktypeclass_id INT NOT NULL,
                description LONGTEXT NOT NULL,
                selectable TINYINT(1) NOT NULL,
                FOREIGN KEY(tasktypeclass_id) REFERENCES TaskTypeClass (id))",
            "CREATE UNIQUE INDEX UNIQ_F7737B3C5E237E06 ON TaskType (name)",
            "CREATE INDEX IDX_F7737B3CAEA19A54 ON TaskType (tasktypeclass_id)"
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {   
        $this->statements['mysql'] = array(
            "ALTER TABLE TaskType DROP FOREIGN KEY FK_F7737B3CAEA19A54"
        );
        
        $this->addCommonStatement("DROP TABLE TaskType");      
        
        parent::down($schema);
    }   
}