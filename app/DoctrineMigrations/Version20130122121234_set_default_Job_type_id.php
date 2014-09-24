<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130122121234_set_default_Job_type_id extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "UPDATE Job SET type_id = 1"
            ],
            'down' => [
                "SELECT 1 + 1"
            ]
        ],
        'sqlite' => [
            'up' => [
                "UPDATE Job SET type_id = 1"
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