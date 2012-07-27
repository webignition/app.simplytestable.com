<?php

namespace Application\Migrations;

use SimplyTestable\ApiBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120716214726_create_Worker_Job_TaskType_Task extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE Worker (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_981EBA54F47645AE (url), PRIMARY KEY(id)) ENGINE = InnoDB",
            "CREATE TABLE Job (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                website_id INT NOT NULL,
                state_id INT NOT NULL,
                INDEX IDX_C395A618A76ED395 (user_id),
                INDEX IDX_C395A61818F45C82 (website_id),
                INDEX IDX_C395A6185D83CC1 (state_id),
                PRIMARY KEY(id)) ENGINE = InnoDB",
            "CREATE TABLE TaskType (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_F7737B3C5E237E06 (name), PRIMARY KEY(id)) ENGINE = InnoDB",
            "CREATE TABLE Task (
                id INT AUTO_INCREMENT NOT NULL,
                job_id INT NOT NULL,
                state_id INT NOT NULL,
                worker_id INT DEFAULT NULL,
                url LONGTEXT NOT NULL,
                INDEX IDX_F24C741BBE04EA9 (job_id),
                INDEX IDX_F24C741B5D83CC1 (state_id),
                INDEX IDX_F24C741B6B20BA36 (worker_id),
                PRIMARY KEY(id)) ENGINE = InnoDB",
            "ALTER TABLE Job ADD CONSTRAINT FK_C395A618A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)",
            "ALTER TABLE Job ADD CONSTRAINT FK_C395A61818F45C82 FOREIGN KEY (website_id) REFERENCES WebSite (id)",
            "ALTER TABLE Job ADD CONSTRAINT FK_C395A6185D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741BBE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741B5D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741B6B20BA36 FOREIGN KEY (worker_id) REFERENCES Worker (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE Worker (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, url VARCHAR(255) NOT NULL)",
            "CREATE UNIQUE INDEX UNIQ_981EBA54F47645AE ON Worker (url)",
            "CREATE TABLE Job (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INT NOT NULL,
                website_id INT NOT NULL,
                state_id INT NOT NULL,
                FOREIGN KEY(user_id) REFERENCES fos_user (id),
                FOREIGN KEY(website_id) REFERENCES WebSite (id),
                FOREIGN KEY(state_id) REFERENCES State (id))", 
            "CREATE INDEX IDX_C395A618A76ED395 ON Job (user_id)",
            "CREATE INDEX IDX_C395A61818F45C82 ON Job (website_id)",
            "CREATE INDEX IDX_C395A6185D83CC1 ON Job (state_id)",
            "CREATE TABLE TaskType (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)",
            "CREATE UNIQUE INDEX UNIQ_F7737B3C5E237E06 ON TaskType (name)",
            "CREATE TABLE Task (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                job_id INT NOT NULL,
                state_id INT NOT NULL,
                worker_id INT DEFAULT NULL,
                url LONGTEXT NOT NULL,
                FOREIGN KEY(job_id) REFERENCES Job (id),
                FOREIGN KEY(state_id) REFERENCES State (id),
                FOREIGN KEY(worker_id) REFERENCES Worker (id))"                
        );
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE Task DROP FOREIGN KEY FK_F24C741B6B20BA36",
            "ALTER TABLE Task DROP FOREIGN KEY FK_F24C741BBE04EA9",
            "DROP TABLE Worker",
            "DROP TABLE Job",
            "DROP TABLE TaskType",
            "DROP TABLE Task"
        );
        
        $this->statements['sqlite'] = array(
            "DROP TABLE Worker",
            "DROP TABLE Job",
            "DROP TABLE TaskType",
            "DROP TABLE Task"
        );      
        
        parent::down($schema);
    }    
}
