<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;
/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130104214616_Add_Worker_State extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE Worker ADD state_id INT DEFAULT NULL",
            "ALTER TABLE Worker ADD CONSTRAINT FK_981EBA545D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)",
            "CREATE INDEX IDX_981EBA545D83CC1 ON Worker (state_id)"
        );
        
        $this->statements['sqlite'] = array(
            "ALTER TABLE Worker ADD state_id INT DEFAULT NULL",
            // Creating of foreign key constraint removed for sqlite as it is not supported
            "CREATE INDEX IDX_981EBA545D83CC1 ON Worker (state_id)"
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE Worker DROP FOREIGN KEY FK_981EBA545D83CC1",
            "DROP INDEX IDX_981EBA545D83CC1 ON Worker",
            "ALTER TABLE Worker DROP state_id"
        );      
        
        $this->statements['sqlite'] = array(
            "SELECT 1 + 1"
        );
        
        parent::down($schema);
    }   
}
