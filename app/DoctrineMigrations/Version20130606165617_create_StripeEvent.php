<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;


/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130606165617_create_StripeEvent extends BaseMigration
{   
    public function up(Schema $schema)
    {        
        $this->statements['mysql'] = array(
            "CREATE TABLE StripeEvent (
                id INT AUTO_INCREMENT NOT NULL,
                stripeId VARCHAR(255) NOT NULL,
                type VARCHAR(255) NOT NULL,
                isLive TINYINT(1) NOT NULL,
                UNIQUE INDEX UNIQ_EC94E394C355FC8E (stripeId),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE StripeEvent (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                stripeId VARCHAR(255) NOT NULL,
                type VARCHAR(255) NOT NULL,
                isLive TINYINT(1) NOT NULL)",
            "CREATE UNIQUE INDEX UNIQ_EC94E394C355FC8E ON StripeEvent (stripeId)",

        );     
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->addCommonStatement("DROP TABLE StripeEvent");      
        
        parent::down($schema);
    }    
}
