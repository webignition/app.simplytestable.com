<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130326155956_add_TimePeriod_indexes extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE INDEX start_idx ON TimePeriod (startDateTime)",
                "CREATE INDEX end_idx ON TimePeriod (endDateTime)"
            ],
            'down' => [
                "DROP INDEX start_idx ON TimePeriod",
                "DROP INDEX end_idx ON TimePeriod"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE INDEX start_idx ON TimePeriod (startDateTime)",
                "CREATE INDEX end_idx ON TimePeriod (endDateTime)"
            ],
            'down' => [
                "DROP INDEX start_idx ON TimePeriod",
                "DROP INDEX end_idx ON TimePeriod"
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