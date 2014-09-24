<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130611110708_add_UserAccountPlan_stripeCustomer extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE UserAccountPlan ADD stripeCustomer VARCHAR(255) DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE UserAccountPlan DROP stripeCustomer"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE UserAccountPlan ADD stripeCustomer VARCHAR(255) DEFAULT NULL",
            ],
            'down' => [
                "ALTER TABLE UserAccountPlan DROP stripeCustomer"
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