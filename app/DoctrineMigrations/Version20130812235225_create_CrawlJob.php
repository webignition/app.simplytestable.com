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
                parent_job_id INT NOT NULL,
                crawl_job_id INT NOT NULL,
                state_id INT NOT NULL,
                INDEX IDX_7CB90CF45D83CC1 (state_id),
                UNIQUE INDEX UNIQ_7CB90CF4C04B9157 (crawl_job_id),
                UNIQUE INDEX UNIQ_7CB90CF444F38D6F (parent_job_id),
                PRIMARY KEY(id)
             )DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE CrawlJob ADD CONSTRAINT FK_7CB90CF45D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)",
            "ALTER TABLE CrawlJob ADD CONSTRAINT FK_7CB90CF4C04B9157 FOREIGN KEY (crawl_job_id) REFERENCES Job (id)",
            "ALTER TABLE CrawlJob ADD CONSTRAINT FK_7CB90CF444F38D6F FOREIGN KEY (parent_job_id) REFERENCES Job (id)"            
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE CrawlJob (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                parent_job_id INT NOT NULL,
                crawl_job_id INT NOT NULL,
                state_id INT NOT NULL,
                FOREIGN KEY(state_id) REFERENCES State (id),
                FOREIGN KEY(crawl_job_id) REFERENCES Job (id),
                FOREIGN KEY(parent_job_id) REFERENCES Job (id)
             )",
            "CREATE INDEX IDX_7CB90CF45D83CC1 ON CrawlJob (state_id)",
            "CREATE UNIQUE INDEX UNIQ_7CB90CF4C04B9157 ON CrawlJob (crawl_job_id)",
            "CREATE UNIQUE INDEX UNIQ_7CB90CF444F38D6F ON CrawlJob (parent_job_id)"            
        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("DROP TABLE CrawlJob");      
        
        parent::down($schema);
    }      
}
