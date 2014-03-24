<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20140324145641_alter_WebSite_canonicalUrl extends BaseMigration
{
//    public function up(Schema $schema)
//    {
//        // this up() migration is autogenerated, please modify it to your needs
//        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
//        
//        $this->addSql("ALTER TABLE WebSite CHANGE canonicalUrl canonicalUrl LONGTEXT NOT NULL");
//    }
//
//    public function down(Schema $schema)
//    {
//        // this down() migration is autogenerated, please modify it to your needs
//        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
//        
//        $this->addSql("ALTER TABLE WebSite CHANGE canonicalUrl canonicalUrl VARCHAR(255) NOT NULL");
//    }
    
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "ALTER TABLE WebSite DROP INDEX UNIQ_28E0CB454A404188",
            "ALTER TABLE  `WebSite` CHANGE  `canonicalUrl`  `canonicalUrl` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL",
            "ALTER TABLE  `dev_app_simplytestable_com`.`WebSite` ADD UNIQUE  `UNIQ_28E0CB454A404188` (  `canonicalUrl` ( 255 ) )"
        );
        
        $this->statements['sqlite'] = array(
            "SELECT 1 + 1",
//            "ALTER TABLE WebSite DROP INDEX UNIQ_28E0CB454A404188",
//            "ALTER TABLE  `WebSite` CHANGE  `canonicalUrl`  `canonicalUrl` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL",
//            "ALTER TABLE  `dev_app_simplytestable_com`.`WebSite` ADD UNIQUE  `UNIQ_28E0CB454A404188` (  `canonicalUrl` ( 255 ) )"
        );
        
        parent::up($schema);
    }
   

    public function down(Schema $schema)
    {           
        $this->statements['mysql'] = array(
            "ALTER TABLE WebSite DROP INDEX UNIQ_28E0CB454A404188",
            "ALTER TABLE  `WebSite` CHANGE  `canonicalUrl`  `canonicalUrl` VARHCHAR(255) NOT NULL",
            "ALTER TABLE  `dev_app_simplytestable_com`.`WebSite` ADD UNIQUE  `UNIQ_28E0CB454A404188` (  `canonicalUrl` ( 255 ) )"
        );
        
        $this->statements['sqlite'] = array(
            "SELECT 1 + 1",
//            "ALTER TABLE WebSite DROP INDEX UNIQ_28E0CB454A404188",
//            "ALTER TABLE  `WebSite` CHANGE  `canonicalUrl`  `canonicalUrl` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL",
//            "ALTER TABLE  `dev_app_simplytestable_com`.`WebSite` ADD UNIQUE  `UNIQ_28E0CB454A404188` (  `canonicalUrl` ( 255 ) )"
        );        
        
        //$this->addCommonStatement("ALTER TABLE WebSite CHANGE canonicalUrl canonicalUrl VARCHAR(255) NOT NULL");       
        //$this->addCommonStatement('SELECT 1 + 1');
        
        parent::down($schema);
    }    
}
