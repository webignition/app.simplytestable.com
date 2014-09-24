<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20140707141202_create_Team extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE Team (
                    id INT AUTO_INCREMENT NOT NULL,
                    leader_id INT NOT NULL,
                    INDEX IDX_64D20921A76ED395 (leader_id),
                    name VARCHAR(255) NOT NULL,
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE Team ADD CONSTRAINT FK_64D20921A76ED395 FOREIGN KEY (leader_id) REFERENCES fos_user (id)",
                "CREATE UNIQUE INDEX UNIQ_64D209215E237E06 ON Team (name)"
            ],
            'down' => [
                "DROP TABLE Team"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE Team (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    leader_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    FOREIGN KEY(leader_id) REFERENCES fos_user (id)
                 )",
                "CREATE UNIQUE INDEX UNIQ_64D209215E237E06 ON Team (name)",
                "CREATE INDEX IDX_64D20921A76ED395 ON Team (leader_id)"
            ],
            'down' => [
                "DROP TABLE Team"
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