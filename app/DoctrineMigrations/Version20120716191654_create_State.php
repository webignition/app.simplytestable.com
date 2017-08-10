<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120716191654_create_State extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE State (
                    id INT AUTO_INCREMENT NOT NULL, 
                    name VARCHAR(255) NOT NULL, 
                    nextState_id INT DEFAULT NULL, 
                    UNIQUE INDEX UNIQ_6252FDFF5E237E06 (name), 
                    UNIQUE INDEX UNIQ_6252FDFF4A689548 (nextState_id), 
                    PRIMARY KEY(id)) 
                    DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE State ADD CONSTRAINT FK_6252FDFF4A689548 FOREIGN KEY (nextState_id) REFERENCES State (id)"
            ],
            'down' => [
                "ALTER TABLE State DROP FOREIGN KEY FK_6252FDFF4A689548",
                "DROP TABLE State"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE State (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    nextState_id INT DEFAULT NULL,
                    FOREIGN KEY(nextState_id) REFERENCES State (id))",
                "CREATE UNIQUE INDEX UNIQ_6252FDFF5E237E06 ON State (name)",
                "CREATE UNIQUE INDEX UNIQ_6252FDFF4A689548 ON State (nextState_id)"
            ],
            'down' => [
                "DROP TABLE State"
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
