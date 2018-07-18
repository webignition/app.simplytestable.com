<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130104214616_Add_Worker_State extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE Worker ADD state_id INT DEFAULT NULL",
                "ALTER TABLE Worker ADD CONSTRAINT FK_981EBA545D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)",
                "CREATE INDEX IDX_981EBA545D83CC1 ON Worker (state_id)"
            ],
            'down' => [
                "ALTER TABLE Worker DROP FOREIGN KEY FK_981EBA545D83CC1",
                "DROP INDEX IDX_981EBA545D83CC1 ON Worker",
                "ALTER TABLE Worker DROP state_id"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE Worker ADD state_id INT DEFAULT NULL",
                // Creating of foreign key constraint removed for sqlite as it is not supported
                "CREATE INDEX IDX_981EBA545D83CC1 ON Worker (state_id)"
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