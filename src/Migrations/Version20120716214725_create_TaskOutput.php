<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120716214725_create_TaskOutput extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE TaskOutput (
                    id INT AUTO_INCREMENT NOT NULL,
                    output LONGTEXT DEFAULT NULL,
                    contentType VARCHAR(255) DEFAULT NULL,
                    errorCount INT NOT NULL,
                    PRIMARY KEY(id))
                    DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB"
            ],
            'down' => [
                "DROP TABLE TaskOutput"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE TaskOutput (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    output LONGTEXT DEFAULT NULL COLLATE NOCASE,
                    contentType VARCHAR(255) DEFAULT NULL COLLATE NOCASE,
                    errorCount INT NOT NULL)"
            ],
            'down' => [
                "DROP TABLE TaskOutput"
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