<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130521154624_create_JobAmmendment extends BaseMigration
{    
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "CREATE TABLE JobAmmendment (
                id INT AUTO_INCREMENT NOT NULL,
                job_id INT NOT NULL,
                constraint_id INT DEFAULT NULL,
                reason VARCHAR(255) NOT NULL,
                INDEX IDX_E1E6DB74BE04EA9 (job_id),
                INDEX IDX_E1E6DB74E3087FFC (constraint_id),
                PRIMARY KEY(id))
                DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE JobAmmendment ADD CONSTRAINT FK_E1E6DB74BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)",
            "ALTER TABLE JobAmmendment ADD CONSTRAINT FK_E1E6DB74E3087FFC FOREIGN KEY (constraint_id) REFERENCES AccountPlanConstraint (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE JobAmmendment (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                job_id INT NOT NULL,
                constraint_id INT DEFAULT NULL,
                reason VARCHAR(255) NOT NULL,
                FOREIGN KEY(job_id) REFERENCES Job (id),
                FOREIGN KEY(constraint_id) REFERENCES AccountPlanConstraint (id))",
            "CREATE INDEX IDX_E1E6DB74BE04EA9 ON JobAmmendment (job_id)",
            "CREATE INDEX IDX_E1E6DB74E3087FFC ON JobAmmendment (constraint_id)"
        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("DROP TABLE JobAmmendment");      
        
        parent::down($schema);
    }      
}
