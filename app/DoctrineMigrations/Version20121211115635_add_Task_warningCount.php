<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20121211115635_add_Task_warningCount extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE TaskOutput ADD warningCount INT NOT NULL"
            ],
            'down' => [
                "ALTER TABLE TaskOutput DROP warningCount"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE TaskOutput ADD warningCount INT DEFAULT 0 NOT NULL"
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