<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;
/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120713173328_create_WebSite extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE WebSite (id INT AUTO_INCREMENT NOT NULL, canonicalUrl VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_28E0CB454A404188 (canonicalUrl), PRIMARY KEY(id)) ENGINE = InnoDB"
            ],
            'down' => [
                "DROP TABLE WebSite"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE WebSite (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, canonicalUrl LONGTEXT NOT NULL)",
                "CREATE INDEX canonicalUrl_idx ON WebSite (canonicalUrl)",
            ],
            'down' => [
                "DROP TABLE WebSite"
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
