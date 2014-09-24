<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20131016144353_add_Job_isPublic extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE Job ADD isPublic TINYINT(1) NOT NULL"
            ],
            'down' => [
                "ALTER TABLE Job DROP isPublic"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE Job ADD isPublic TINYINT(1) NOT NULL DEFAULT 0"
            ],
            'down' => [
                "ALTER TABLE Job DROP isPublic"
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