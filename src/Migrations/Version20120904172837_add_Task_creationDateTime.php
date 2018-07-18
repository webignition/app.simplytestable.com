<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120904172837_add_Task_creationDateTime extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE Task ADD creationDateTime DATETIME NOT NULL",
                "CREATE INDEX creationDateTime_idx ON Task (creationDateTime)"
            ],
            'down' => [
                "ALTER TABLE Task DROP creationDateTime",
                "DROP INDEX creationDateTime_idx ON Task"
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