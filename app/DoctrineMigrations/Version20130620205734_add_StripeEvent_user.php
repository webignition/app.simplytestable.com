<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130620205734_add_StripeEvent_user extends BaseMigration
{    
    
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "ALTER TABLE StripeEvent ADD user_id INT DEFAULT NULL",
            "ALTER TABLE StripeEvent ADD CONSTRAINT FK_EC94E394A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)",
            "CREATE INDEX IDX_EC94E394A76ED395 ON StripeEvent (user_id)"
        );
        
        $this->statements['sqlite'] = array(
            "ALTER TABLE StripeEvent ADD user_id INT DEFAULT NULL",
            // Cannot alter table to add contraint in sqlite.
            //"ALTER TABLE StripeEvent ADD CONSTRAINT FK_EC94E394A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)",
            "CREATE INDEX IDX_EC94E394A76ED395 ON StripeEvent (user_id)"            
        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->statements['mysql'] = array(
            "ALTER TABLE StripeEvent DROP FOREIGN KEY FK_EC94E394A76ED395",
            "DROP INDEX IDX_EC94E394A76ED395 ON StripeEvent",
            "ALTER TABLE StripeEvent DROP user_id"
        );
        
        $this->statements['sqlite'] = array(
            //"ALTER TABLE StripeEvent DROP FOREIGN KEY FK_EC94E394A76ED395",
            "DROP INDEX IDX_EC94E394A76ED395 ON StripeEvent",
            "ALTER TABLE StripeEvent DROP user_id"
        );        
        
        parent::down($schema);
    }     
}
