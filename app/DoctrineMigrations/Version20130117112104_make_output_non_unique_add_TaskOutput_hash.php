<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130117112104_make_output_non_unique_add_TaskOutput_hash extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE Task DROP INDEX UNIQ_F24C741BDE097880, ADD INDEX IDX_F24C741BDE097880 (output_id)",
                "ALTER TABLE TaskOutput ADD hash VARCHAR(32) DEFAULT NULL",
                "CREATE INDEX hash_idx ON TaskOutput (hash)"
            ],
            'down' => [
                "ALTER TABLE Task DROP INDEX IDX_F24C741BDE097880, ADD UNIQUE INDEX UNIQ_F24C741BDE097880 (output_id)",
                "DROP INDEX hash_idx ON TaskOutput",
                "ALTER TABLE TaskOutput DROP hash"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE INDEX IDX_F24C741BDE097880 ON Task (output_id)",
                "ALTER TABLE TaskOutput ADD hash VARCHAR(32) DEFAULT NULL",
                "CREATE INDEX hash_idx ON TaskOutput (hash)"
            ],
            'down' => [
                "ALTER TABLE Task ADD UNIQUE INDEX UNIQ_F24C741BDE097880 (output_id)"
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