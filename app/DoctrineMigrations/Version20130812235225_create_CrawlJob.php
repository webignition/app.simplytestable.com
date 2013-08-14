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
            "CREATE TABLE CrawlJobContainer (
                id INT AUTO_INCREMENT NOT NULL,
                parent_job_id INT NOT NULL,
                crawl_job_id INT NOT NULL,
                UNIQUE INDEX UNIQ_7CB90CF4C04B9157 (crawl_job_id),
                UNIQUE INDEX UNIQ_7CB90CF444F38D6F (parent_job_id),
                PRIMARY KEY(id)
             )DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE CrawlJobContainer ADD CONSTRAINT FK_7CB90CF4C04B9157 FOREIGN KEY (crawl_job_id) REFERENCES Job (id)",
            "ALTER TABLE CrawlJobContainer ADD CONSTRAINT FK_7CB90CF444F38D6F FOREIGN KEY (parent_job_id) REFERENCES Job (id)"            
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE CrawlJobContainer (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                parent_job_id INT NOT NULL,
                crawl_job_id INT NOT NULL,
                FOREIGN KEY(crawl_job_id) REFERENCES Job (id),
                FOREIGN KEY(parent_job_id) REFERENCES Job (id)
             )",
            "CREATE UNIQUE INDEX UNIQ_7CB90CF4C04B9157 ON CrawlJobContainer (crawl_job_id)",
            "CREATE UNIQUE INDEX UNIQ_7CB90CF444F38D6F ON CrawlJobContainer (parent_job_id)"            
        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("DROP TABLE CrawlJobContainer");      
        
        parent::down($schema);
    }      
}
