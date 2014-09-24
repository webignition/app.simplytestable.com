<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130612105750_add_Account_Plan_stripe_id extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE AccountPlan ADD stripe_id VARCHAR(255) DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE AccountPlan DROP stripe_id"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE AccountPlan ADD stripe_id VARCHAR(255) DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE AccountPlan DROP stripe_id"
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