<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130607130241_add_AccountPlan_isPremium extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE AccountPlan ADD isPremium TINYINT(1) DEFAULT NULL"
            ],
            'down' => [
                "ALTER TABLE AccountPlan DROP isPremium"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE AccountPlan ADD isPremium TINYINT(1) DEFAULT NULL",
            ],
            'down' => [
                "ALTER TABLE AccountPlan DROP isPremium"
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