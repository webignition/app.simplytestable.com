<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130122113752_create_JobType extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE JobType (
                    id INT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description LONGTEXT NOT NULL,
                    UNIQUE INDEX UNIQ_6AEF4BE05E237E06 (name),
                    PRIMARY KEY(id))
                    DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB"
            ],
            'down' => [
                "DROP TABLE JobType"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE JobType (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL COLLATE NOCASE,
                    description LONGTEXT NOT NULL)",
                "CREATE UNIQUE INDEX UNIQ_6AEF4BE05E237E06 ON JobType (name)",
            ],
            'down' => [
                "DROP TABLE JobType"
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