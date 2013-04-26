<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130425145821_add_AccountPlan extends BaseMigration
{    
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE AccountPlan (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                isVisible TINYINT(1) NOT NULL,
                UNIQUE INDEX UNIQ_F6643B305E237E06 (name),
                PRIMARY KEY(id))
                DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE AccountPlanConstraint ADD plan_id INT",
            "ALTER TABLE AccountPlanConstraint ADD CONSTRAINT FK_E18FF0B7E3087FFC FOREIGN KEY (plan_id) REFERENCES AccountPlan (id)",
            "CREATE INDEX IDX_E18FF0B7E3087FFC ON AccountPlanConstraint (plan_id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE AccountPlan (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                isVisible TINYINT(1) NOT NULL)",
            "CREATE UNIQUE INDEX UNIQ_F6643B305E237E06 ON AccountPlan (name)",
            "ALTER TABLE AccountPlanConstraint ADD plan_id INT",
            
            // Creating of foreign key constraint removed for sqlite as it is not supported
            "CREATE INDEX IDX_E18FF0B7E3087FFC ON AccountPlanConstraint (plan_id)"
        ); 
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "ALTER TABLE AccountPlanConstraint DROP FOREIGN KEY FK_E18FF0B7E3087FFC",
            "DROP TABLE AccountPlan",
            "DROP INDEX IDX_E18FF0B7E3087FFC ON AccountPlanConstraint",
            "ALTER TABLE AccountPlanConstraint DROP constraint_id"
        );
        
        $this->statements['sqlite'] = array(
            "ALTER TABLE AccountPlanConstraint DROP FOREIGN KEY FK_E18FF0B7E3087FFC",
            "DROP TABLE AccountPlan",
            "DROP INDEX IDX_E18FF0B7E3087FFC ON AccountPlanConstraint",
            "ALTER TABLE AccountPlanConstraint DROP constraint_id"
        );    
        
        parent::down($schema);
    }     
}
