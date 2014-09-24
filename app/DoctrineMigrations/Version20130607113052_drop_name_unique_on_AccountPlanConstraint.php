<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130607113052_drop_name_unique_on_AccountPlanConstraint extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "DROP INDEX UNIQ_E18FF0B75E237E06 ON AccountPlanConstraint"
            ],
            'down' => [
                "CREATE UNIQUE INDEX UNIQ_E18FF0B75E237E06 ON AccountPlanConstraint (name)"
            ]
        ],
        'sqlite' => [
            'up' => [
                "DROP INDEX UNIQ_E18FF0B75E237E06",
            ],
            'down' => [
                "CREATE UNIQUE INDEX UNIQ_E18FF0B75E237E06 ON AccountPlanConstraint (name)"
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