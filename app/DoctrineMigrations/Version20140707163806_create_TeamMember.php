<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20140707163806_create_TeamMember extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE TeamMember (
                    id INT AUTO_INCREMENT NOT NULL,
                    team_id INT NOT NULL,
                    user_id INT NOT NULL,
                    INDEX IDX_752B5942296CD8AE (team_id),
                    INDEX IDX_752B5942A76ED395 (user_id),
                    UNIQUE INDEX teamMember_idx (team_id, user_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE TeamMember ADD CONSTRAINT FK_752B5942296CD8AE FOREIGN KEY (team_id) REFERENCES Team (id)",
                "ALTER TABLE TeamMember ADD CONSTRAINT FK_752B5942A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)"
            ],
            'down' => [
                "DROP TABLE TeamMember"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE TeamMember (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    team_id INT NOT NULL,
                    user_id INT NOT NULL,
                    FOREIGN KEY(team_id) REFERENCES Team (id),
                    FOREIGN KEY(user_id) REFERENCES fos_user (id)
                )",
                "CREATE INDEX IDX_752B5942296CD8AE ON TeamMember (team_id)",
                "CREATE INDEX IDX_752B5942A76ED395 ON TeamMember (user_id)",
                "CREATE UNIQUE INDEX teamMember_idx ON TeamMember (team_id, user_id)"
            ],
            'down' => [
                "DROP TABLE TeamMember"
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