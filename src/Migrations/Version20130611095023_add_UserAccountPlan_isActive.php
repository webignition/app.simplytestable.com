<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130611095023_add_UserAccountPlan_isActive extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE UserAccountPlan ADD isActive TINYINT(1) DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE UserAccountPlan DROP isActive"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE UserAccountPlan ADD isActive TINYINT(1) DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE UserAccountPlan DROP isActive"
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