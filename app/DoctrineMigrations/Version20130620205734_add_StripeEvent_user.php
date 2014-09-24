<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130620205734_add_StripeEvent_user extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "ALTER TABLE StripeEvent ADD user_id INT DEFAULT NULL",
                "ALTER TABLE StripeEvent ADD CONSTRAINT FK_EC94E394A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)",
                "CREATE INDEX IDX_EC94E394A76ED395 ON StripeEvent (user_id)"
            ],
            'down' => [
                "ALTER TABLE StripeEvent DROP FOREIGN KEY FK_EC94E394A76ED395",
                "DROP INDEX IDX_EC94E394A76ED395 ON StripeEvent",
                "ALTER TABLE StripeEvent DROP user_id"
            ]
        ],
        'sqlite' => [
            'up' => [
                "ALTER TABLE StripeEvent ADD user_id INT DEFAULT NULL",
                // Cannot alter table to add contraint in sqlite.
                //"ALTER TABLE StripeEvent ADD CONSTRAINT FK_EC94E394A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)",
                "CREATE INDEX IDX_EC94E394A76ED395 ON StripeEvent (user_id)"
            ],
            'down' => [
                //"ALTER TABLE StripeEvent DROP FOREIGN KEY FK_EC94E394A76ED395",
                "DROP INDEX IDX_EC94E394A76ED395 ON StripeEvent",
                "ALTER TABLE StripeEvent DROP user_id"
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