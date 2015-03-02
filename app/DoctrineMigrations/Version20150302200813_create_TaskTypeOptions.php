<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150302200813_create_TaskTypeOptions extends AbstractMigration
{
    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE TaskTypeOptions (
                    id INT AUTO_INCREMENT NOT NULL,
                    tasktype_id INT NOT NULL,
                    options LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
                    INDEX IDX_B46D453E7D6EFC3 (tasktype_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE TaskTypeOptions ADD CONSTRAINT FK_B46D453E7D6EFC3 FOREIGN KEY (tasktype_id) REFERENCES TaskType (id)"
            ],
            'down' => [
                "DROP TABLE TaskTypeOptions"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE TaskTypeOptions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    tasktype_id INT NOT NULL,
                    options LONGTEXT NOT NULL,
                    FOREIGN KEY(tasktype_id) REFERENCES TaskType (id)
                )",
                "CREATE INDEX IDX_B46D453E7D6EFC3 ON TaskTypeOptions (tasktype_id)"
            ],
            'down' => [
                "DROP TABLE TaskTypeOptions"
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
