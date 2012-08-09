<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120716214708_create_Job extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE Job (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                website_id INT NOT NULL,
                state_id INT NOT NULL,
                startDateTime DATETIME DEFAULT NULL,
                INDEX IDX_C395A618A76ED395 (user_id),
                INDEX IDX_C395A61818F45C82 (website_id),
                INDEX IDX_C395A6185D83CC1 (state_id),
                PRIMARY KEY(id)) ENGINE = InnoDB",
            "ALTER TABLE Job ADD CONSTRAINT FK_C395A618A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)",
            "ALTER TABLE Job ADD CONSTRAINT FK_C395A61818F45C82 FOREIGN KEY (website_id) REFERENCES WebSite (id)",
            "ALTER TABLE Job ADD CONSTRAINT FK_C395A6185D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE Job (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INT NOT NULL,
                website_id INT NOT NULL,
                state_id INT NOT NULL,
                startDateTime DATETIME DEFAULT NULL,
                FOREIGN KEY(user_id) REFERENCES fos_user (id),
                FOREIGN KEY(website_id) REFERENCES WebSite (id),
                FOREIGN KEY(state_id) REFERENCES State (id))", 
            "CREATE INDEX IDX_C395A618A76ED395 ON Job (user_id)",
            "CREATE INDEX IDX_C395A61818F45C82 ON Job (website_id)",
            "CREATE INDEX IDX_C395A6185D83CC1 ON Job (state_id)"
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {   
        $this->addCommonStatement("DROP TABLE Job");      
        
        parent::down($schema);
    }    
}
