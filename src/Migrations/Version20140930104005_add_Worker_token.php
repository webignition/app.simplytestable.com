<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140930104005_add_Worker_token extends AbstractMigration
{
    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE Worker ADD token VARCHAR(255) DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE Worker DROP token"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE Worker ADD token VARCHAR(255) DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE Worker DROP token"
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
