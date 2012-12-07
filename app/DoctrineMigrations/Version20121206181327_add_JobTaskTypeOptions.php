<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20121206181327_add_JobTaskTypeOptions extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE JobTaskTypeOptions (
                id INT AUTO_INCREMENT NOT NULL,
                job_id INT NOT NULL,
                tasktype_id INT NOT NULL,
                options LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
                INDEX UNIQ_A72BF044BE04EA9 (job_id),
                INDEX IDX_A72BF0447D6EFC3 (tasktype_id),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE JobTaskTypeOptions ADD CONSTRAINT FK_A72BF044BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)",
            "ALTER TABLE JobTaskTypeOptions ADD CONSTRAINT FK_A72BF0447D6EFC3 FOREIGN KEY (tasktype_id) REFERENCES TaskType (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE JobTaskTypeOptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                job_id INT NOT NULL,
                tasktype_id INT NOT NULL,
                options LONGTEXT NOT NULL,
                FOREIGN KEY (job_id) REFERENCES Job (id),
                FOREIGN KEY (tasktype_id) REFERENCES TaskType (id))",
            "ALTER TABLE Job ADD taskTypeOptions INT DEFAULT NULL",
            "CREATE INDEX UNIQ_A72BF044BE04EA9 ON JobTaskTypeOptions (job_id)",
            "CREATE INDEX IDX_A72BF0447D6EFC3 ON JobTaskTypeOptions (tasktype_id)"
        );       
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->addCommonStatement("DROP TABLE JobTaskTypeOptions");        
        parent::down($schema);
    } 
}
