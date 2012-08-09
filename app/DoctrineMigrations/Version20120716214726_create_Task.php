<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120716214726_create_Task extends BaseMigration
{
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "CREATE TABLE Task (
                id INT AUTO_INCREMENT NOT NULL,
                job_id INT NOT NULL,
                state_id INT NOT NULL,
                worker_id INT DEFAULT NULL,
                url LONGTEXT NOT NULL,
                tasktype_id INT NOT NULL,
                timePeriod_id INT DEFAULT NULL,
                remoteId BIGINT DEFAULT NULL,
                INDEX IDX_F24C741BBE04EA9 (job_id),
                INDEX IDX_F24C741B5D83CC1 (state_id),
                INDEX IDX_F24C741B6B20BA36 (worker_id),
                INDEX IDX_F24C741B7D6EFC3 (tasktype_id),
                UNIQUE INDEX UNIQ_F24C741BE43FFED1 (timePeriod_id),
                INDEX remoteId_idx (remoteId),
                PRIMARY KEY(id)) ENGINE = InnoDB",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741BBE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741B5D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741B6B20BA36 FOREIGN KEY (worker_id) REFERENCES Worker (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741B7D6EFC3 FOREIGN KEY (tasktype_id) REFERENCES TaskType (id)",
            "ALTER TABLE Task ADD CONSTRAINT FK_F24C741BE43FFED1 FOREIGN KEY (timePeriod_id) REFERENCES TimePeriod (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE Task (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                job_id INT NOT NULL,
                state_id INT NOT NULL,
                worker_id INT DEFAULT NULL,
                url LONGTEXT NOT NULL,
                tasktype_id INT NOT NULL,
                timePeriod_id INT DEFAULT NULL,
                remoteId BIGINT DEFAULT NULL,
                FOREIGN KEY(job_id) REFERENCES Job (id),
                FOREIGN KEY(state_id) REFERENCES State (id),
                FOREIGN KEY(worker_id) REFERENCES Worker (id),
                FOREIGN KEY(tasktype_id) REFERENCES TaskType (id),
                FOREIGN KEY(timePeriod_id) REFERENCES TimePeriod (id))",
            "CREATE INDEX IDX_F24C741BBE04EA9 ON Task (job_id)",
            "CREATE INDEX IDX_F24C741B5D83CC1 ON Task (state_id)",
            "CREATE INDEX IDX_F24C741B6B20BA36 ON Task (worker_id)",
            "CREATE INDEX IDX_F24C741B7D6EFC3 ON Task (tasktype_id)",
            "CREATE UNIQUE INDEX UNIQ_F24C741BE43FFED1 ON Task (timePeriod_id)",
            "CREATE INDEX remoteId_idx ON Task (remoteId)"
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "ALTER TABLE Task DROP FOREIGN KEY FK_F24C741B6B20BA36",
            "ALTER TABLE Task DROP FOREIGN KEY FK_F24C741BBE04EA9",
            "ALTER TABLE Task DROP FOREIGN KEY FK_F24C741B7D6EFC3",
            "DROP TABLE Task"
        );
        
        $this->statements['sqlite'] = array(
            "DROP TABLE Task"
        );      
        
        parent::down($schema);
    }    
}