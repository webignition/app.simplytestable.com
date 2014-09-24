<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130621132138_add_StripeEvent_type_idx extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE INDEX type_idx ON StripeEvent (type)"
            ],
            'down' => [
                "DROP INDEX type_idx ON StripeEvent"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE INDEX type_idx ON StripeEvent (type)"
            ],
            'down' => [
                "DROP INDEX type_idx ON StripeEvent"
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