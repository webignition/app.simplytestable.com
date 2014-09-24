<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130514111323_create_UserEmailChangeRequest extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE UserEmailChangeRequest (
                    id INT AUTO_INCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    newEmail VARCHAR(255) NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    UNIQUE INDEX UNIQ_587D54278F9B97B6 (newEmail),
                    UNIQUE INDEX UNIQ_587D54275F37A13B (token),
                    INDEX IDX_587D5427A76ED395 (user_id),
                    PRIMARY KEY(id))
                    DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE UserEmailChangeRequest ADD CONSTRAINT FK_587D5427A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)"
            ],
            'down' => [
                "DROP TABLE UserEmailChangeRequest"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE UserEmailChangeRequest (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    newEmail VARCHAR(255) NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    FOREIGN KEY(user_id) REFERENCES fos_user (id))",
                "CREATE UNIQUE INDEX UNIQ_587D54278F9B97B6 ON UserEmailChangeRequest (newEmail)",
                "CREATE UNIQUE INDEX UNIQ_587D54275F37A13B ON UserEmailChangeRequest (token)",
                "CREATE INDEX IDX_587D5427A76ED395 ON UserEmailChangeRequest (user_id)"
            ],
            'down' => [
                "DROP TABLE UserEmailChangeRequest"
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