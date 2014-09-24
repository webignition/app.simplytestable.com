<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20140218165539_add_StripeEvent_isProcessed extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE StripeEvent ADD isProcessed TINYINT(1) DEFAULT '0' NOT NULL"
            ],
            'down' => [
                "ALTER TABLE StripeEvent DROP isProcessed"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE StripeEvent ADD isProcessed TINYINT(1) DEFAULT '0' NOT NULL"
            ],
            'down' => [
                "ALTER TABLE StripeEvent DROP isProcessed"
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