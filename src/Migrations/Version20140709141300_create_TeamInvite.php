<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20140709141300_create_TeamInvite extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE TeamInvite (
                    id INT AUTO_INCREMENT NOT NULL,
                    team_id INT NOT NULL,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    INDEX IDX_C22DB3ED296CD8AE (team_id),
                    INDEX IDX_C22DB3EDA76ED395 (user_id),
                    UNIQUE INDEX teamInvite_idx (team_id, user_id),
                    UNIQUE INDEX UNIQ_C22DB3ED5F37A13B (token),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE TeamInvite ADD CONSTRAINT FK_C22DB3ED296CD8AE FOREIGN KEY (team_id) REFERENCES Team (id)",
                "ALTER TABLE TeamInvite ADD CONSTRAINT FK_C22DB3EDA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)"
            ],
            'down' => [
                "DROP TABLE TeamInvite"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE TeamInvite (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    team_id INT NOT NULL,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    FOREIGN KEY(team_id) REFERENCES Team (id),
                    FOREIGN KEY(user_id) REFERENCES fos_user (id)
                )",
                "CREATE INDEX IDX_C22DB3ED296CD8AE ON TeamInvite (team_id)",
                "CREATE INDEX IDX_C22DB3EDA76ED395 ON TeamInvite (user_id)",
                "CREATE UNIQUE INDEX teamInvite_idx ON TeamInvite (team_id, user_id)",
                "CREATE UNIQUE INDEX UNIQ_C22DB3ED5F37A13B ON TeamInvite (token)"
            ],
            'down' => [
                "DROP TABLE TeamInvite"
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