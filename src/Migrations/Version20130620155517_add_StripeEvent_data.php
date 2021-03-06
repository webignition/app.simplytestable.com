<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130620155517_add_StripeEvent_data extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE StripeEvent ADD data LONGTEXT DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE StripeEvent DROP data"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE StripeEvent ADD data LONGTEXT DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE StripeEvent DROP data"
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