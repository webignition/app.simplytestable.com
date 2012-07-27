<?php

namespace Application\Migrations;

use SimplyTestable\ApiBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120713173328_create_WebSite extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE WebSite (id INT AUTO_INCREMENT NOT NULL, canonicalUrl VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_28E0CB454A404188 (canonicalUrl), PRIMARY KEY(id)) ENGINE = InnoDB"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE WebSite (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, canonicalUrl VARCHAR(255) NOT NULL)",
            "CREATE UNIQUE INDEX UNIQ_28E0CB454A404188 ON WebSite (canonicalUrl)",
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "DROP TABLE WebSite"
        );
        
        $this->statements['sqlite'] = array(
            "DROP TABLE WebSite"
        );      
        
        parent::down($schema);
    }
}
