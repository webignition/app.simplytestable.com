<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130812235225_create_CrawlJob extends BaseMigration
{    
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "CREATE TABLE CrawlJob (
                id INT AUTO_INCREMENT NOT NULL,
                job_id INT NOT NULL,
                state_id INT NOT NULL,
                UNIQUE INDEX UNIQ_7CB90CF4BE04EA9 (job_id),
                INDEX IDX_7CB90CF45D83CC1 (state_id),
                PRIMARY KEY(id)
             )DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE CrawlJob ADD CONSTRAINT FK_7CB90CF4BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)",
            "ALTER TABLE CrawlJob ADD CONSTRAINT FK_7CB90CF45D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE CrawlJob (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                job_id INT NOT NULL,
                state_id INT NOT NULL,
                FOREIGN KEY(job_id) REFERENCES Job (id),
                FOREIGN KEY(state_id) REFERENCES State (id)                
             )",
            "CREATE UNIQUE INDEX UNIQ_7CB90CF4BE04EA9 ON CrawlJob (job_id)",
            "CREATE INDEX IDX_7CB90CF45D83CC1 ON CrawlJob (state_id)"
        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("DROP TABLE CrawlJob");      
        
        parent::down($schema);
    }      
}
