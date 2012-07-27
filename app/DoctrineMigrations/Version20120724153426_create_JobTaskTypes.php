<?php

namespace Application\Migrations;

use SimplyTestable\ApiBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120724153426_create_JobTaskTypes extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE JobTaskTypes (
                tasktype_id INT NOT NULL,
                job_id INT NOT NULL,
                INDEX IDX_6520FB147D6EFC3 (tasktype_id),
                INDEX IDX_6520FB14BE04EA9 (job_id),
                PRIMARY KEY(job_id, tasktype_id)) ENGINE = InnoDB",
            "ALTER TABLE JobTaskTypes ADD CONSTRAINT FK_6520FB147D6EFC3 FOREIGN KEY (tasktype_id) REFERENCES TaskType (id)",
            "ALTER TABLE JobTaskTypes ADD CONSTRAINT FK_6520FB14BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE JobTaskTypes (
                tasktype_id INT NOT NULL,
                job_id INT NOT NULL,
                PRIMARY KEY (job_id, tasktype_id),
                FOREIGN KEY(tasktype_id) REFERENCES TaskType (id)
                FOREIGN KEY(job_id) REFERENCES Job (id))",
            "CREATE INDEX IDX_6520FB147D6EFC3 ON JobTaskTypes (tasktype_id)",
            "CREATE INDEX IDX_6520FB14BE04EA9 ON JobTaskTypes (job_id)"
        );
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {    
        $this->addCommonStatement("DROP TABLE JobTaskTypes");      
        
        parent::down($schema);
    }
}
