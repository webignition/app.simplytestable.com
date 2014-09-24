<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120716214707_create_TimePeriod extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE TimePeriod (id INT AUTO_INCREMENT NOT NULL, startDateTime DATETIME DEFAULT NULL, endDateTime DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB"
            ],
            'down' => [
                "DROP TABLE TimePeriod"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE TimePeriod (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    startDateTime DATETIME DEFAULT NULL,
                    endDateTime DATETIME DEFAULT NULL)"
            ],
            'down' => [
                "DROP TABLE TimePeriod"
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