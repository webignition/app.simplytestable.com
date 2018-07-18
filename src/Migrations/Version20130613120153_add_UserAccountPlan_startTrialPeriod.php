<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130613120153_add_UserAccountPlan_startTrialPeriod extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE UserAccountPlan ADD startTrialPeriod INT DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE UserAccountPlan DROP startTrialPeriod"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE UserAccountPlan ADD startTrialPeriod INT DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE UserAccountPlan DROP startTrialPeriod"
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