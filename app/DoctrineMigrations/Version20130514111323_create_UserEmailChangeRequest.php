<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130514111323_create_UserEmailChangeRequest extends BaseMigration
{   
    
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "CREATE TABLE UserEmailChangeRequest (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                newEmail VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL,
                UNIQUE INDEX UNIQ_587D54278F9B97B6 (newEmail),
                UNIQUE INDEX UNIQ_587D54275F37A13B (token),
                INDEX IDX_587D5427A76ED395 (user_id),
                PRIMARY KEY(id))
                DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE UserEmailChangeRequest ADD CONSTRAINT FK_587D5427A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE UserEmailChangeRequest (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INT NOT NULL,
                newEmail VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL,
                FOREIGN KEY(user_id) REFERENCES fos_user (id))",
            "CREATE UNIQUE INDEX UNIQ_587D54278F9B97B6 ON UserEmailChangeRequest (newEmail)",
            "CREATE UNIQUE INDEX UNIQ_587D54275F37A13B ON UserEmailChangeRequest (token)",
            "CREATE INDEX IDX_587D5427A76ED395 ON UserEmailChangeRequest (user_id)"
        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("DROP TABLE UserEmailChangeRequest");      
        
        parent::down($schema);
    }     
}
