<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20140709141300_create_TeamInvite extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE TeamInvite (
                id INT AUTO_INCREMENT NOT NULL,
                team_id INT NOT NULL,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                INDEX token_idx (token),
                INDEX IDX_C22DB3ED296CD8AE (team_id),
                INDEX IDX_C22DB3EDA76ED395 (user_id),
                UNIQUE INDEX teamInvite_idx (team_id, user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE TeamInvite ADD CONSTRAINT FK_C22DB3ED296CD8AE FOREIGN KEY (team_id) REFERENCES Team (id)",
            "ALTER TABLE TeamInvite ADD CONSTRAINT FK_C22DB3EDA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)"
        );

        $this->statements['sqlite'] = array(
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
            "CREATE INDEX token_idx ON TeamInvite (token)"
        );

        parent::up($schema);
    }


    public function down(Schema $schema)
    {
        $this->addCommonStatement("DROP TABLE TeamInvite");
        parent::down($schema);
    }
}
