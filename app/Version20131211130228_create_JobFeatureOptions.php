<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131211130228_create_JobFeatureOptions extends BaseMigration
{   
    
    public function up(Schema $schema)
    {           
        $this->statements['mysql'] = array(
            "CREATE TABLE JobFeatureOptions (
                id INT AUTO_INCREMENT NOT NULL,
                job_id INT NOT NULL,
                options LONGTEXT DEFAULT NULL,
                UNIQUE INDEX UNIQ_78E796F6BE04EA9 (job_id),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE JobFeatureOptions ADD CONSTRAINT FK_78E796F6BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE JobFeatureOptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                job_id INT NOT NULL,
                options LONGTEXT DEFAULT NULL,
                FOREIGN KEY(job_id) REFERENCES Job (id))",
            "CREATE INDEX IDX_78E796F6BE04EA9 ON JobFeatureOptions (job_id)",
        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {            
        $this->addCommonStatement("DROP TABLE JobFeatureOptions");              
        parent::down($schema);
    }      
}
