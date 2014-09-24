<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120716214712_create_TaskType extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE TaskType (
                    id INT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    tasktypeclass_id INT NOT NULL,
                    description LONGTEXT NOT NULL,
                    selectable TINYINT(1) NOT NULL,
                    UNIQUE INDEX UNIQ_F7737B3C5E237E06 (name),
                    INDEX IDX_F7737B3CAEA19A54 (tasktypeclass_id),
                    PRIMARY KEY(id)) ENGINE = InnoDB",
                "ALTER TABLE TaskType ADD CONSTRAINT FK_F7737B3CAEA19A54 FOREIGN KEY (tasktypeclass_id) REFERENCES TaskTypeClass (id)"
            ],
            'down' => [
                "ALTER TABLE TaskType DROP FOREIGN KEY FK_F7737B3CAEA19A54",
                "DROP TABLE TaskType"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE TaskType (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL COLLATE NOCASE,
                    tasktypeclass_id INT NOT NULL,
                    description LONGTEXT NOT NULL,
                    selectable TINYINT(1) NOT NULL,
                    FOREIGN KEY(tasktypeclass_id) REFERENCES TaskTypeClass (id))",
                "CREATE UNIQUE INDEX UNIQ_F7737B3C5E237E06 ON TaskType (name)",
                "CREATE INDEX IDX_F7737B3CAEA19A54 ON TaskType (tasktypeclass_id)"
            ],
            'down' => [
                "DROP TABLE TaskType"
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