<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130606165617_create_StripeEvent extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE StripeEvent (
                    id INT AUTO_INCREMENT NOT NULL,
                    stripeId VARCHAR(255) NOT NULL,
                    type VARCHAR(255) NOT NULL,
                    isLive TINYINT(1) NOT NULL,
                    UNIQUE INDEX UNIQ_EC94E394C355FC8E (stripeId),
                    PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB"
            ],
            'down' => [
                "DROP TABLE StripeEvent"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE StripeEvent (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    stripeId VARCHAR(255) NOT NULL,
                    type VARCHAR(255) NOT NULL,
                    isLive TINYINT(1) NOT NULL)",
                "CREATE UNIQUE INDEX UNIQ_EC94E394C355FC8E ON StripeEvent (stripeId)",
            ],
            'down' => [
                "DROP TABLE StripeEvent"
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