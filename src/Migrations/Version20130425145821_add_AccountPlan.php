<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130425145821_add_AccountPlan extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE AccountPlan (
                    id INT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    isVisible TINYINT(1) NOT NULL,
                    UNIQUE INDEX UNIQ_F6643B305E237E06 (name),
                    PRIMARY KEY(id))
                    DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB"
            ],
            'down' => [
                "DROP TABLE AccountPlan"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE AccountPlan (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    isVisible TINYINT(1) NOT NULL)",
                "CREATE UNIQUE INDEX UNIQ_F6643B305E237E06 ON AccountPlan (name)"
            ],
            'down' => [
                "DROP TABLE AccountPlan"
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