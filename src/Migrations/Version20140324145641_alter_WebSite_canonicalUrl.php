<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20140324145641_alter_WebSite_canonicalUrl extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE WebSite DROP INDEX UNIQ_28E0CB454A404188",
                "ALTER TABLE  `WebSite` CHANGE  `canonicalUrl`  `canonicalUrl` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL",
                "ALTER TABLE  `WebSite` ADD INDEX  `canonicalUrl_idx` (  `canonicalUrl` ( 255 ) )"
            ],
            'down' => [
                "ALTER TABLE WebSite DROP INDEX canonicalUrl_idx",
                "ALTER TABLE  `WebSite` CHANGE  `canonicalUrl`  `canonicalUrl` VARHCHAR(255) NOT NULL",
                "ALTER TABLE  `WebSite` ADD UNIQUE  `UNIQ_28E0CB454A404188` (  `canonicalUrl` ( 255 ) )"
            ]
        ],
        'sqlite' => [
            'up' => [
                "SELECT 1 + 1"
            ],
            'down' => [
                "SELECT 1 + 1"
            ]
        ]
    ];

    public function up(Schema $schema)
    {
        foreach ($this->statements[$this->connection->getDatabasePlatform()->getName()]['up'] as $statement) {
            $this->addSql($statement);
        }
    }

    public function down(Schema $schema)
    {
        foreach ($this->statements[$this->connection->getDatabasePlatform()->getName()]['down'] as $statement) {
            $this->addSql($statement);
        }
    }

}