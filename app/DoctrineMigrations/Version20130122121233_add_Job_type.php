<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130122121233_add_Job_type extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE Job ADD type_id INT DEFAULT NULL",
                "ALTER TABLE Job ADD CONSTRAINT FK_C395A618C54C8C93 FOREIGN KEY (type_id) REFERENCES JobType (id)",
                "CREATE INDEX IDX_C395A618C54C8C93 ON Job (type_id)"
            ],
            'down' => [
                "ALTER TABLE Job DROP FOREIGN KEY FK_C395A618C54C8C93",
                "DROP INDEX IDX_C395A618C54C8C93 ON Job",
                "ALTER TABLE Job DROP type_id"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE Job ADD type_id INT DEFAULT NULL",
                "CREATE INDEX IDX_C395A618C54C8C93 ON Job (type_id)"
            ],
            'down' => [
                "SELECT 1 + 1"
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