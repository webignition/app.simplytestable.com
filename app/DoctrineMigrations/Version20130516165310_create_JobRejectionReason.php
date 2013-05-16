<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130516165310_create_JobRejectionReason extends BaseMigration
{    
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "CREATE TABLE JobRejectionReason (
                id INT AUTO_INCREMENT NOT NULL,
                job_id INT DEFAULT NULL,
                constraint_id INT DEFAULT NULL,
                reason VARCHAR(255) NOT NULL,
                UNIQUE INDEX UNIQ_F769EE08BE04EA9 (job_id),
                INDEX IDX_F769EE08E3087FFC (constraint_id),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE JobRejectionReason ADD CONSTRAINT FK_F769EE08BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)",
            "ALTER TABLE JobRejectionReason ADD CONSTRAINT FK_F769EE08E3087FFC FOREIGN KEY (constraint_id) REFERENCES AccountPlanConstraint (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE JobRejectionReason (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                job_id INT DEFAULT NULL,
                constraint_id INT DEFAULT NULL,
                reason VARCHAR(255) NOT NULL,
                FOREIGN KEY(job_id) REFERENCES Job (id),
                FOREIGN KEY(constraint_id) REFERENCES AccountPlanConstraint (id))",
            "CREATE UNIQUE INDEX UNIQ_F769EE08BE04EA9 ON JobRejectionReason (job_id)",
            "CREATE INDEX IDX_F769EE08E3087FFC ON JobRejectionReason (constraint_id)"
        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("DROP TABLE JobRejectionReason");      
        
        parent::down($schema);
    }     
}
