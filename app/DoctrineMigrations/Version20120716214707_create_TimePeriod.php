<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120716214707_create_TimePeriod extends BaseMigration
{
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "CREATE TABLE TimePeriod (id INT AUTO_INCREMENT NOT NULL, startDateTime DATETIME DEFAULT NULL, endDateTime DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE TimePeriod (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                startDateTime DATETIME DEFAULT NULL,
                endDateTime DATETIME DEFAULT NULL)"             
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->addCommonStatement("DROP TABLE TimePeriod");        
        parent::down($schema);
    }
}
