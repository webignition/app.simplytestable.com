<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20131211155715_add_Job_parameters extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE Job ADD parameters LONGTEXT DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE Job DROP parameters"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE Job ADD parameters LONGTEXT DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE Job DROP parameters"
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