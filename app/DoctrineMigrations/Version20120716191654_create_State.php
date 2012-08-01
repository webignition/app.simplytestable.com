<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120716191654_create_State extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE State (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, nextState_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6252FDFF5E237E06 (name), UNIQUE INDEX UNIQ_6252FDFF4A689548 (nextState_id), PRIMARY KEY(id)) ENGINE = InnoDB",
            "ALTER TABLE State ADD CONSTRAINT FK_6252FDFF4A689548 FOREIGN KEY (nextState_id) REFERENCES State (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE State (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                nextState_id INT DEFAULT NULL,
                FOREIGN KEY(nextState_id) REFERENCES State (id))",
            "CREATE UNIQUE INDEX UNIQ_6252FDFF5E237E06 ON State (name)",
            "CREATE UNIQUE INDEX UNIQ_6252FDFF4A689548 ON State (nextState_id)"                           
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE State DROP FOREIGN KEY FK_6252FDFF4A689548",
            "DROP TABLE State"
        );
        
        $this->statements['sqlite'] = array(
            "DROP TABLE State"
        );      
        
        parent::down($schema);
    }
}
