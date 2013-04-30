<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130426095747_add_UserAccountPlan extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE UserAccountPlan (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                accountplan_id INT NOT NULL,
                INDEX IDX_BA8D333AA76ED395 (user_id),
                INDEX IDX_BA8D333A369E2B6B (accountplan_id),
                PRIMARY KEY(id))
                DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE UserAccountPlan ADD CONSTRAINT FK_BA8D333AA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)",
            "ALTER TABLE UserAccountPlan ADD CONSTRAINT FK_BA8D333A369E2B6B FOREIGN KEY (accountplan_id) REFERENCES AccountPlan (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE UserAccountPlan (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INT NOT NULL,
                accountplan_id INT NOT NULL,
                FOREIGN KEY(user_id) REFERENCES fos_user (id),
                FOREIGN KEY(accountplan_id) REFERENCES AccountPlan (id))",
            "CREATE INDEX IDX_BA8D333AA76ED395 ON UserAccountPlan (user_id)",
            "CREATE INDEX IDX_BA8D333A369E2B6B ON UserAccountPlan (accountplan_id)",           
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {      
        $this->addCommonStatement("DROP TABLE UserAccountPlan"); 
        
        parent::down($schema);
    }      
}
