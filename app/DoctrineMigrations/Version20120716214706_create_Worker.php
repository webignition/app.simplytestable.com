<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120716214706_create_Worker extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE Worker (id INT AUTO_INCREMENT NOT NULL, hostname VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_981EBA54F47645AE (hostname), PRIMARY KEY(id)) ENGINE = InnoDB"
            ],
            'down' => [
                "DROP TABLE Worker"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE Worker (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, hostname VARCHAR(255) NOT NULL)",
                "CREATE UNIQUE INDEX UNIQ_981EBA54F47645AE ON Worker (hostname)"
            ],
            'down' => [
                "DROP TABLE Worker"
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
