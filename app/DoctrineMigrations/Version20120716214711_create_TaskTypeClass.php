<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120716214711_create_TaskTypeClass extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE TaskTypeClass (
                    id INT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description LONGTEXT DEFAULT NULL,
                    UNIQUE INDEX UNIQ_F92FE5F25E237E06 (name),
                    PRIMARY KEY(id)) 
                    DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB"
            ],
            'down' => [
                "DROP TABLE TaskTypeClass"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE TaskTypeClass (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description LONGTEXT DEFAULT NULL)",
                "CREATE UNIQUE INDEX UNIQ_F92FE5F25E237E06 ON TaskTypeClass (name)"
            ],
            'down' => [
                "DROP TABLE TaskTypeClass"
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